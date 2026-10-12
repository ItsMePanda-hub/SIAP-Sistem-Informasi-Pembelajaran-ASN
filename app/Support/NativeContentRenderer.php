<?php

namespace App\Support;

class NativeContentRenderer
{
    public static function blocks(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $blocks = [];
        $listBuffer = null;

        foreach ($lines as $raw) {
            $line = trim($raw);
            if ($line === '') {
                if ($listBuffer) {
                    $blocks[] = $listBuffer;
                    $listBuffer = null;
                }
                continue;
            }

            if (self::isHeading($line)) {
                if ($listBuffer) {
                    $blocks[] = $listBuffer;
                    $listBuffer = null;
                }
                $level = self::headingLevel($line);
                $blocks[] = ['type' => 'heading', 'level' => $level, 'text' => $line];
                continue;
            }

            if (preg_match('/^(\d+)[.)]\s+(.+)$/u', $line, $m)) {
                if (!$listBuffer || $listBuffer['ordered'] !== true) {
                    if ($listBuffer) $blocks[] = $listBuffer;
                    $listBuffer = ['type' => 'list', 'ordered' => true, 'items' => []];
                }
                $listBuffer['items'][] = $m[2];
                continue;
            }

            if (preg_match('/^[-•]\s+(.+)$/u', $line, $m)) {
                if (!$listBuffer || $listBuffer['ordered'] !== false) {
                    if ($listBuffer) $blocks[] = $listBuffer;
                    $listBuffer = ['type' => 'list', 'ordered' => false, 'items' => []];
                }
                $listBuffer['items'][] = $m[1];
                continue;
            }

            if ($listBuffer) {
                $blocks[] = $listBuffer;
                $listBuffer = null;
            }
            $blocks[] = ['type' => 'paragraph', 'text' => $line];
        }

        if ($listBuffer) $blocks[] = $listBuffer;

        return $blocks;
    }

    private static function isHeading(string $line): bool
    {
        if (preg_match('/^BAB\s+\d+\s*[—\-]/u', $line)) return true;
        $upper = mb_strtoupper($line);
        $heads = ['RINGKASAN', 'LATIHAN', 'GLOSARIUM', 'REFERENSI', 'DAFTAR ISI', 'TUJUAN PEMBELAJARAN', 'PRASYARAT', 'PENDAHULUAN'];
        foreach ($heads as $h) {
            if ($upper === $h || str_starts_with($upper, $h . ' ') || str_starts_with($upper, $h . ' —') || str_starts_with($upper, $h . ' -')) {
                if (mb_strlen($line) < 90) return true;
            }
        }
        if (preg_match('/^(RINGKASAN|LATIHAN|GLOSARIUM|REFERENSI).*$/iu', $line) && mb_strlen($line) < 80) {
            if (str_contains($line, '—') || str_contains($line, ':') || $line === mb_strtoupper($line)) return true;
        }
        return false;
    }

    private static function headingLevel(string $line): int
    {
        if (preg_match('/^BAB\s+\d+/u', $line)) return 2;
        $upper = mb_strtoupper(trim($line));
        if (in_array($upper, ['GLOSARIUM', 'REFERENSI', 'REFERENSI DAN BACAAN LANJUTAN', 'DAFTAR ISI'], true)) return 2;
        if (str_starts_with($upper, 'RINGKASAN') || str_starts_with($upper, 'LATIHAN')) return 2;
        return 3;
    }
}
