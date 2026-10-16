<?php

namespace App\Services;

use App\Enums\LearningMaterialType;
use App\Models\LearningMaterial;
use App\Models\ReadingProgress;
use App\Models\User;

class LibraryNativeSummaryService
{
    private const MAX_CROSS = 5;
    private const MAX_CHARS_PER_MATERIAL = 8000;
    private const MAX_TOTAL_CHARS = 14000;

    public function isLibraryNativeIntent(string $message): bool
    {
        $m = mb_strtolower($message, 'UTF-8');
        if (str_contains($m, 'materi')) return true;
        return (bool) preg_match('/rangkum|ringkas|poin penting|poin utama|jelaskan ulang|bandingkan|lintas/i', $message);
    }

    public function buildForUser(User $user, string $message): ?array
    {
        if (! $this->isLibraryNativeIntent($message)) return null;

        $allNatives = LearningMaterial::where('material_type', LearningMaterialType::Native->value)->get()->filter(fn ($mat) => $user->can('view', $mat))->values();
        $matchedNatives = $this->matchByTitle($allNatives, $message);

        $isCross = (bool) preg_match('/beberapa|lintas|bandingkan|semua|berbagai|gabungan|cross/i', $message);

        if ($isCross || count($matchedNatives) > 1) {
            $materials = count($matchedNatives) > 0 ? $matchedNatives->take(self::MAX_CROSS) : $allNatives->take(self::MAX_CROSS);
            if ($materials->isEmpty()) {
                return ['earlyReply' => 'Saat ini tidak ada materi Native yang dapat Anda akses untuk dirangkum. Pastikan materi sudah berstatus Published dan Anda memiliki akses.'];
            }
            $filtered = $materials->filter(fn ($mat) => $mat->hasContent());
            if ($filtered->isEmpty()) {
                return ['earlyReply' => 'Materi Native yang dapat Anda akses belum memiliki konten sehingga belum dapat dirangkum.'];
            }
            return $this->buildContext($filtered, $user, 'cross', $message);
        }

        if (count($matchedNatives) === 1) {
            $mat = $matchedNatives->first();
            if (! $mat->hasContent()) {
                return ['earlyReply' => 'Konten materi "'.$mat->title.'" belum tersedia sehingga belum dapat dirangkum.'];
            }
            return $this->buildContext(collect([$mat]), $user, 'single', $message);
        }

        if (count($matchedNatives) === 0) {
            $candidates = $this->extractCandidateNames($message);
            if (! empty($candidates)) {
                foreach ($candidates as $cand) {
                    $like = trim($cand);
                    if ($like === '' || mb_strlen($like) < 3) continue;
                    $foundAny = LearningMaterial::where('title', 'like', '%'.$like.'%')->get();
                    if ($foundAny->isEmpty()) continue;
                    $foundNativeAccessible = $foundAny->filter(fn ($mat) => $mat->isNative() && $user->can('view', $mat));
                    if ($foundNativeAccessible->isNotEmpty()) {
                        $withContent = $foundNativeAccessible->filter(fn ($mat) => $mat->hasContent());
                        if ($withContent->isEmpty()) {
                            return ['earlyReply' => 'Konten materi "'.$foundNativeAccessible->first()->title.'" belum tersedia sehingga belum dapat dirangkum.'];
                        }
                        return $this->buildContext($withContent->take(1), $user, 'single', $message);
                    }
                    $foundNonNative = $foundAny->filter(fn ($mat) => ! $mat->isNative());
                    if ($foundNonNative->isNotEmpty()) {
                        $accessibleNonNative = $foundNonNative->filter(fn ($mat) => $user->can('view', $mat));
                        if ($accessibleNonNative->isNotEmpty()) {
                            return ['earlyReply' => 'Materi "'.$accessibleNonNative->first()->title.'" bertipe '.strtoupper($accessibleNonNative->first()->material_type->value).' dan tidak tersedia untuk rangkuman. Rangkuman hanya tersedia untuk materi bertipe Native.'];
                        }
                        return ['earlyReply' => 'Materi yang Anda maksud tidak ditemukan atau tidak dapat diakses. Pastikan judul materi Native sudah benar dan status publikasi memungkinkan akses.'];
                    }
                    $foundInaccessibleNative = $foundAny->filter(fn ($mat) => $mat->isNative() && ! $user->can('view', $mat));
                    if ($foundInaccessibleNative->isNotEmpty()) {
                        return ['earlyReply' => 'Materi yang Anda maksud tidak ditemukan atau tidak dapat diakses. Pastikan judul materi Native sudah benar dan status publikasi memungkinkan akses.'];
                    }
                }
                return ['earlyReply' => 'Maaf, materi yang Anda maksud tidak ditemukan atau tidak tersedia. Pastikan judul materi Native sudah benar dan status publikasi memungkinkan akses.'];
            }

            $isQuestion = (bool) preg_match('/apa|bagaimana|mengapa|jelaskan|poin|bandingkan/i', $message);
            if ($isQuestion && $allNatives->isNotEmpty()) {
                $withContent = $allNatives->filter(fn ($mat) => $mat->hasContent())->take(1);
                if ($withContent->isNotEmpty()) return $this->buildContext($withContent, $user, 'single', $message);
            }

            return ['earlyReply' => 'Maaf, materi yang Anda maksud tidak ditemukan atau tidak tersedia. Pastikan judul materi Native sudah benar dan status publikasi memungkinkan akses.'];
        }

        return null;
    }

    private function buildContext($materials, User $user, string $mode, string $message): array
    {
        $ids = $materials->pluck('id')->all();
        $progressMap = ReadingProgress::where('user_id', $user->id)->whereIn('learning_material_id', $ids)->pluck('progress_percent', 'learning_material_id')->all();

        $total = 0;
        $items = [];
        foreach ($materials as $mat) {
            $content = trim((string) $mat->content);
            $content = mb_substr($content, 0, self::MAX_CHARS_PER_MATERIAL);
            if ($total + mb_strlen($content) > self::MAX_TOTAL_CHARS) {
                $remaining = self::MAX_TOTAL_CHARS - $total;
                if ($remaining <= 200) break;
                $content = mb_substr($content, 0, $remaining);
            }
            $total += mb_strlen($content);
            $pp = $progressMap[$mat->id] ?? null;
            $items[] = [
                'id' => $mat->id,
                'title' => $mat->title,
                'slug' => $mat->slug,
                'content' => $content,
                'progress_percent' => $pp,
                'has_content' => true,
            ];
        }

        $addendum = $this->systemAddendum($mode, count($items) > 1);

        $context = [
            'intent' => $mode === 'cross' ? 'rangkuman_lintas_materi' : 'rangkuman_satu_materi',
            'mode' => $mode,
            'message' => mb_substr($message, 0, 1000),
            'materials' => $items,
            'rules' => [
                'Hanya gunakan konten Native yang ada di library_native.materials[].content',
                'Jangan gunakan isi PDF, file, atau LinkOnly; jangan buat URL/file fallback',
                'Jika jawaban tidak ada di konten, katakan "Informasi tersebut tidak tercantum dalam materi" dan jangan mengarang SOP/kebijakan/prosedur resmi',
                'Pengetahuan umum AI jangan disajikan seolah dari Library; bedakan eksplisit jika dipakai',
                'Untuk rangkuman lintas materi, bedakan sumber tiap poin dengan menyebut judul materi asalnya',
                'Progres membaca bukan bukti pemahaman; jangan menolak membantu walau progres <100%, boleh sarankan melanjutkan membaca',
                'Jangan mengubah progres membaca',
            ],
            'progress_note' => 'Progres membaca hanya saran belajar, bukan syarat menjawab',
        ];

        return [
            'context' => $context,
            'system_addendum' => $addendum,
            'materials' => $materials,
            'progressMap' => $progressMap,
        ];
    }

    private function systemAddendum(string $mode, bool $multi): string
    {
        $base = "ATURAN RANGKUMAN LIBRARY NATIVE:\n- Hanya rangkum dari library_native.materials[].content yang diberikan. Jangan ambil dari PDF, file, LinkOnly, URL, atau sumber lain.\n- Setiap materi di konteks sudah tervalidasi bertipe Native dan dapat diakses user; jangan tambah materi lain.\n- Jika materi tidak menjelaskan jawaban, jawab persis: \"Informasi tersebut tidak tercantum dalam materi.\" Jangan mengarang SOP, kebijakan, prosedur, atau persyaratan resmi.\n- Pengetahuan umum AI tidak boleh disajikan seolah berasal dari Library. Jika perlu, bedakan eksplisit: \"Di luar materi, secara umum...\" atau batasi pada konteks.\n- Jangan menolak pertanyaan walau progress_percent <100; boleh sarankan \"Anda dapat melanjutkan membaca materi untuk pendalaman\" tanpa menolak.\n- Jangan mengubah progres membaca.\n- Jangan klaim progres 100% berarti sudah paham.\n";
        if ($mode === 'cross' || $multi) {
            $base .= "- Rangkuman lintas materi: bedakan sumber tiap poin dengan format \"[Judul Materi]\" di tiap poin.\n";
        }
        $base .= "- Balas dalam bahasa Indonesia sopan, paragraf pendek, tanpa markdown (* #).\n";
        return $base;
    }

    private function matchByTitle($materials, string $message): \Illuminate\Support\Collection
    {
        $msgLower = mb_strtolower($message, 'UTF-8');
        $msgTokens = $this->tokens($msgLower);
        $matched = collect();
        foreach ($materials as $mat) {
            $titleLower = mb_strtolower($mat->title, 'UTF-8');
            if (mb_strpos($msgLower, $titleLower) !== false) {
                $matched->push($mat);
                continue;
            }
            $titleTokens = $this->tokens($titleLower);
            if (empty($titleTokens)) continue;
            $hits = 0;
            foreach ($titleTokens as $t) {
                if (in_array($t, $msgTokens, true)) $hits++;
            }
            $ratio = count($titleTokens) > 0 ? $hits / count($titleTokens) : 0;
            if ($ratio >= 0.5) $matched->push($mat);
        }
        return $matched->unique('id')->values();
    }

    private function tokens(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $parts = preg_split('/\s+/', mb_strtolower($text, 'UTF-8'));
        $parts = array_filter($parts, fn ($w) => mb_strlen($w) >= 3);
        return array_values(array_unique($parts));
    }

    private function extractCandidateNames(string $message): array
    {
        $candidates = [];
        if (preg_match_all('/materi\s+["\']?([^"\',.\n?!]+)["\']?/iu', $message, $m)) {
            foreach ($m[1] as $raw) {
                $c = trim($raw);
                $c = preg_replace('/^(tentang|mengenai|berjudul|dengan judul)\s+/iu', '', $c);
                $c = trim($c, " \"'“”‘’");
                $stop = ['yang', 'dan', 'serta', 'atau', 'saya', 'boleh', 'dapat', 'diakses', 'untuk', 'dari', 'ini', 'itu'];
                $tokens = preg_split('/\s+/', $c);
                $filtered = [];
                foreach ($tokens as $tok) {
                    if (in_array(mb_strtolower($tok, 'UTF-8'), $stop, true) && count($tokens) > 2) continue;
                    $filtered[] = $tok;
                }
                $c = trim(implode(' ', $filtered));
                if ($c !== '' && mb_strlen($c) >= 3) $candidates[] = mb_substr($c, 0, 120);
            }
        }
        if (preg_match('/rangkum\s+(.+?)(?:\.|$)/iu', $message, $mm)) {
            $c = trim($mm[1]);
            $c = preg_replace('/^(materi\s+)/iu', '', $c);
            if ($c !== '' && mb_strlen($c) >= 3 && mb_strlen($c) < 80) {
                $c2 = trim(preg_replace('/\s+(yang|dan|atau).*/iu', '', $c));
                if (! in_array(mb_strtolower($c2, 'UTF-8'), ['materi', 'saya', 'ini'], true)) $candidates[] = $c2;
            }
        }
        return array_values(array_unique($candidates));
    }
}
