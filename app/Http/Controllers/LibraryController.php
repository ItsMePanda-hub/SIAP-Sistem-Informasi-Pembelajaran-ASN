<?php

namespace App\Http\Controllers;

use App\Enums\LearningLicenseType;
use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LibraryBookmark;
use App\Models\ReadingProgress;
use App\Services\LibraryRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryController extends Controller
{
    public function index(Request $request, LibraryRecommendationService $recommender)
    {
        $user = $request->user();
        $isManager = $this->isManager($user);

        $q = LearningMaterial::with(['level', 'category', 'uploader', 'source']);

        if (! $isManager) {
            $q->current();
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);
            $like = "%{$escaped}%";
            $q->where(function ($qq) use ($like) {
                $qq->whereRaw("title LIKE ? ESCAPE '\\'", [$like])
                    ->orWhereRaw("description LIKE ? ESCAPE '\\'", [$like]);
            });
        }

        if (in_array($scope = $request->query('scope'), LearningMaterialScope::values(), true)) {
            $q->where('scope', $scope);
        }

        if (($levelId = $request->query('learning_level_id')) !== null && $levelId !== '' && ctype_digit((string) $levelId)) {
            $q->where('learning_level_id', (int) $levelId);
        }

        if (($catId = $request->query('learning_category_id')) !== null && $catId !== '' && ctype_digit((string) $catId)) {
            $q->where('learning_category_id', (int) $catId);
        }

        if (in_array($type = $request->query('material_type'), LearningMaterialType::values(), true)) {
            $q->where('material_type', $type);
        }

        if (in_array($pub = $request->query('publication_status'), LearningPublicationStatus::values(), true)) {
            $q->where('publication_status', $pub);
        }

        if (in_array($ver = $request->query('verification_status'), LearningVerificationStatus::values(), true)) {
            $q->where('verification_status', $ver);
        }

        if (in_array($val = $request->query('validity_status'), LearningValidityStatus::values(), true)) {
            $q->where('validity_status', $val);
        }

        if (in_array($prog = $request->query('progress_status'), ['belum_mulai', 'sedang_membaca', 'selesai'], true) && $user) {
            if ($prog === 'belum_mulai') {
                $q->whereDoesntHave('readingProgress', function ($qq) use ($user) {
                    $qq->where('user_id', $user->id)->where('progress_percent', '>', 0);
                });
            } elseif ($prog === 'sedang_membaca') {
                $q->whereHas('readingProgress', function ($qq) use ($user) {
                    $qq->where('user_id', $user->id)->where('progress_percent', '>', 0)->where('progress_percent', '<', 100);
                });
            } else {
                $q->whereHas('readingProgress', function ($qq) use ($user) {
                    $qq->where('user_id', $user->id)->where('progress_percent', '>=', 100);
                });
            }
        }

        $sort = $request->query('sort');
        if ($sort === 'judul_asc') {
            $q->orderBy('title', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'judul_desc') {
            $q->orderBy('title', 'desc')->orderBy('id', 'desc');
        } else {
            $q->latest();
        }

        $materials = $q->paginate(12)->withQueryString();
        $progressMap = [];
        $bookmarkSet = [];
        if ($user) {
            $ids = $materials->pluck('id')->all();
            if (! empty($ids)) {
                $progressMap = ReadingProgress::where('user_id', $user->id)
                    ->whereIn('learning_material_id', $ids)
                    ->pluck('progress_percent', 'learning_material_id')
                    ->all();
                $bookmarkSet = LibraryBookmark::where('user_id', $user->id)
                    ->whereIn('learning_material_id', $ids)
                    ->pluck('learning_material_id')
                    ->all();
                $bookmarkSet = array_flip($bookmarkSet);
            }
        }

        $hasDiscovery = trim((string) $request->query('q', '')) !== ''
            || collect(['scope', 'learning_level_id', 'learning_category_id', 'material_type', 'publication_status', 'verification_status', 'validity_status', 'progress_status', 'sort'])->contains(fn ($k) => $request->filled($k));

        $recommendations = $hasDiscovery ? collect() : $recommender->getRecommendations($user);

        return view('library.index', [
            'materials' => $materials,
            'progressMap' => $progressMap,
            'bookmarkSet' => $bookmarkSet,
            'recommendations' => $recommendations,
            'isManager' => $isManager,
            'levels' => LearningLevel::orderBy('sort_order')->get(),
            'categories' => LearningCategory::orderBy('name')->get(),
            'scopes' => LearningMaterialScope::cases(),
            'types' => LearningMaterialType::cases(),
            'publicationStatuses' => LearningPublicationStatus::cases(),
            'verificationStatuses' => LearningVerificationStatus::cases(),
            'validityStatuses' => LearningValidityStatus::cases(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', LearningMaterial::class);

        return view('library.form', [
            'levels' => LearningLevel::orderBy('sort_order')->get(),
            'categories' => LearningCategory::orderBy('name')->get(),
            'scopes' => LearningMaterialScope::cases(),
            'types' => LearningMaterialType::cases(),
            'publicationStatuses' => LearningPublicationStatus::cases(),
            'verificationStatuses' => LearningVerificationStatus::cases(),
            'validityStatuses' => LearningValidityStatus::cases(),
            'material' => null,
            'editing' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', LearningMaterial::class);

        $validated = $this->validateMaterial($request);
        $validated['uploaded_by'] = $request->user()->id;

        $newPath = null;
        try {
            $this->applyContentFields($request, $validated, null, $newPath);
            LearningMaterial::create($validated);
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }

        return redirect()->route('library.index')->with('status', 'Materi berhasil ditambahkan.');
    }

    public function edit(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        return view('library.form', [
            'levels' => LearningLevel::orderBy('sort_order')->get(),
            'categories' => LearningCategory::orderBy('name')->get(),
            'scopes' => LearningMaterialScope::cases(),
            'types' => LearningMaterialType::cases(),
            'publicationStatuses' => LearningPublicationStatus::cases(),
            'verificationStatuses' => LearningVerificationStatus::cases(),
            'validityStatuses' => LearningValidityStatus::cases(),
            'material' => $learningMaterial,
            'editing' => true,
        ]);
    }

    public function update(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        $validated = $this->validateMaterial($request, $learningMaterial);

        $oldPath = $learningMaterial->file_path;
        $newPath = null;
        $this->applyContentFields($request, $validated, $learningMaterial, $newPath);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($learningMaterial, $validated) {
                $learningMaterial->update($validated);
            });
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }

        if ($oldPath && $oldPath !== $learningMaterial->fresh()->file_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('library.index')->with('status', 'Materi berhasil diperbarui.');
    }

    public function destroy(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('delete', $learningMaterial);

        $learningMaterial->delete();

        return redirect()->route('library.index')->with('status', 'Materi berhasil dihapus.');
    }

    public function read(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('view', $learningMaterial);

        $learningMaterial->load(['level', 'category', 'source.rightsReviewer']);

        try {
            $raw = $learningMaterial->getRawOriginal('material_type');
            $enum = $learningMaterial->material_type;
            $type = $enum instanceof LearningMaterialType ? $enum->value : (string) $raw;
        } catch (\Throwable $e) {
            abort(404);
        }
        abort_unless(in_array($type, LearningMaterialType::values(), true), 404);

        $progress = ReadingProgress::where('user_id', $request->user()->id)
            ->where('learning_material_id', $learningMaterial->id)
            ->first();

        $isBookmarked = LibraryBookmark::where('user_id', $request->user()->id)
            ->where('learning_material_id', $learningMaterial->id)
            ->exists();

        return view('library.read', [
            'learningMaterial' => $learningMaterial,
            'progress' => $progress,
            'isBookmarked' => $isBookmarked,
        ]);
    }

    public function bookmarks(Request $request)
    {
        $user = $request->user();
        $isManager = $this->isManager($user);

        $q = LearningMaterial::with(['level', 'category'])
            ->whereHas('libraryBookmarks', fn ($qq) => $qq->where('user_id', $user->id))
            ->latest();

        $materials = $q->paginate(12)->withQueryString();

        $progressMap = [];
        $ids = $materials->pluck('id')->all();
        if (! empty($ids)) {
            $progressMap = ReadingProgress::where('user_id', $user->id)
                ->whereIn('learning_material_id', $ids)
                ->pluck('progress_percent', 'learning_material_id')
                ->all();
        }

        return view('library.bookmarks', [
            'materials' => $materials,
            'progressMap' => $progressMap,
            'isManager' => $isManager,
        ]);
    }

    public function storeBookmark(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('view', $learningMaterial);

        LibraryBookmark::firstOrCreate([
            'user_id' => $request->user()->id,
            'learning_material_id' => $learningMaterial->id,
        ]);

        return back()->with('status', 'Materi ditambahkan ke bookmark.');
    }

    public function destroyBookmark(Request $request, LearningMaterial $learningMaterial)
    {
        LibraryBookmark::where('user_id', $request->user()->id)
            ->where('learning_material_id', $learningMaterial->id)
            ->delete();

        return back()->with('status', 'Bookmark dihapus.');
    }

    public function updateProgress(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('view', $learningMaterial);

        $validated = $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $incoming = (int) $validated['progress_percent'];
        $now = now();

        $progress = ReadingProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'learning_material_id' => $learningMaterial->id,
        ]);

        $existing = (int) ($progress->progress_percent ?? 0);
        $final = $progress->exists ? max($existing, $incoming) : $incoming;

        $progress->progress_percent = $final;
        if (! $progress->exists || is_null($progress->started_at)) {
            $progress->started_at = $progress->started_at ?? $now;
        }
        $progress->last_read_at = $now;
        if ($final >= 100) {
            $progress->progress_percent = 100;
            $progress->completed_at = $progress->completed_at ?? $now;
        }
        $progress->save();

        return response()->json([
            'progress_percent' => (int) $progress->progress_percent,
            'completed' => $progress->isCompleted(),
        ]);
    }

    public function pdf(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('view', $learningMaterial);

        if (! $this->isManager($request->user())) {
            abort_unless($learningMaterial->isCurrent(), 403, 'Materi tidak tersedia.');
        }

        abort_if($learningMaterial->isLinkOnly(), 404);
        abort_unless($learningMaterial->isPdf() && $learningMaterial->hasFile(), 404);
        abort_unless(Storage::disk('public')->exists($learningMaterial->file_path), 404);

        $path = Storage::disk('public')->path($learningMaterial->file_path);

        return response()->file($path, [
            'Content-Type' => $learningMaterial->file_mime ?? 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($learningMaterial->file_original_name ?? basename($path)).'"',
        ]);
    }

    public function editSource(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        $learningMaterial->load(['source.rightsReviewer', 'source.rightsLogs.reviewer', 'uploader', 'lifecycleLogs.actor']);

        return view('library.source-form', [
            'material' => $learningMaterial,
            'source' => $learningMaterial->source,
            'licenses' => LearningLicenseType::cases(),
            'rightsLogs' => $learningMaterial->source?->rightsLogs ?? collect(),
            'lifecycleLogs' => $learningMaterial->lifecycleLogs()->with('actor')->latest('id')->limit(20)->get(),
        ]);
    }

    public function updateSource(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        $validated = $request->validate([
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_type' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'document_identifier' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'source_issued_at' => ['nullable', 'date'],
            'license_type' => ['nullable', Rule::in(LearningLicenseType::values())],
            'license_url' => ['nullable', 'url', 'max:2048'],
            'last_checked_at' => ['nullable', 'date'],
            'verification_notes' => ['nullable', 'string'],
            'rights_status' => ['nullable', Rule::in(['unreviewed', 'approved', 'rejected'])],
            'rights_review_notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($learningMaterial->isCore()) {
            $request->validate([
                'source_name' => ['required', 'string', 'max:255'],
                'publisher' => ['required', 'string', 'max:255'],
                'license_type' => ['required', Rule::in(LearningLicenseType::values())],
            ]);

            $license = LearningLicenseType::tryFrom((string) $request->input('license_type'));
            if ($license && $license->requiresEvidence()) {
                $request->validate([
                    'license_url' => ['required', 'url', 'max:2048'],
                ]);
            }
        }

        if ($learningMaterial->isLinkOnly()) {
            $request->validate([
                'source_url' => ['required', 'url', 'max:2048'],
            ]);
        }

        $rightsStatus = $request->input('rights_status', 'unreviewed');
        if ($rightsStatus === 'approved') {
            $user = $request->user();
            if ($learningMaterial->uploaded_by !== null && (int) $learningMaterial->uploaded_by === (int) $user->id) {
                throw ValidationException::withMessages([
                    'rights_status' => ['Reviewer hak cipta / hak redistribusi tidak boleh sama dengan pengunggah materi (self-approval tidak diizinkan).'],
                ]);
            }
            $validated['rights_status'] = 'approved';
            $validated['rights_reviewed_by'] = $user->id;
            $validated['rights_reviewed_at'] = now();
        } elseif ($rightsStatus === 'rejected') {
            $validated['rights_status'] = 'rejected';
            $validated['rights_reviewed_by'] = $request->user()->id;
            $validated['rights_reviewed_at'] = now();
        } else {
            $validated['rights_status'] = 'unreviewed';
            $validated['rights_reviewed_by'] = null;
            $validated['rights_reviewed_at'] = null;
        }

        if (! empty($validated['metadata'])) {
            $validated['metadata'] = array_filter($validated['metadata'], fn ($v) => $v !== null && $v !== '');
        }

        $source = $learningMaterial->source()->updateOrCreate(
            ['learning_material_id' => $learningMaterial->id],
            $validated
        );

        // Record append-only rights log
        $source->recordRightsLog(
            decision: $validated['rights_status'],
            reviewerId: $validated['rights_reviewed_by'],
            notes: $validated['rights_review_notes'] ?? null
        );

        return redirect()->route('library.index')->with('status', 'Provenance dan hak cipta berhasil diperbarui.');
    }

    public function publish(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($learningMaterial) {
                $learningMaterial->publish();
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('library.index')->with('status', 'Materi berhasil dipublikasikan.');
    }

    public function unpublish(Request $request, LearningMaterial $learningMaterial)
    {
        $this->authorize('update', $learningMaterial);

        \Illuminate\Support\Facades\DB::transaction(function () use ($learningMaterial) {
            $learningMaterial->unpublish();
        });

        return redirect()->route('library.index')->with('status', 'Materi berhasil di-unpublish.');
    }

    private function validateMaterial(Request $request, ?LearningMaterial $material = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['required', Rule::in(LearningMaterialScope::values())],
            'material_type' => ['required', Rule::in(LearningMaterialType::values())],
            'learning_level_id' => ['required', 'exists:learning_levels,id'],
            'learning_category_id' => ['required', 'exists:learning_categories,id'],
            'publication_status' => ['required', Rule::in(LearningPublicationStatus::values())],
            'verification_status' => ['required', Rule::in(LearningVerificationStatus::values())],
            'validity_status' => ['required', Rule::in(LearningValidityStatus::values())],
            'content' => ['nullable', 'string'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];

        $type = $request->input('material_type');

        if ($type === LearningMaterialType::Native->value) {
            $rules['content'] = ['required', 'string'];
            $rules['pdf_file'] = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
        } elseif ($type === LearningMaterialType::Pdf->value) {
            $rules['content'] = ['nullable', 'string'];
            $needsFile = is_null($material) || ! $material->hasFile();
            if ($needsFile) {
                $rules['pdf_file'] = ['required', 'file', 'mimes:pdf', 'max:10240'];
            } else {
                $rules['pdf_file'] = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
            }
        } elseif ($type === LearningMaterialType::LinkOnly->value) {
            $rules['content'] = ['nullable', 'string'];
            $rules['pdf_file'] = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
        }

        return $request->validate($rules);
    }

    private function applyContentFields(Request $request, array &$validated, ?LearningMaterial $material, ?string &$newPath = null): void
    {
        $type = $validated['material_type'] ?? $request->input('material_type');

        if ($type === LearningMaterialType::Native->value) {
            $validated['content'] = $request->input('content');
            $validated['file_path'] = null;
            $validated['file_original_name'] = null;
            $validated['file_mime'] = null;
            $validated['file_size'] = null;
            unset($validated['pdf_file']);
        } elseif ($type === LearningMaterialType::Pdf->value) {
            $validated['content'] = null;

            if ($request->hasFile('pdf_file')) {
                $file = $request->file('pdf_file');
                $newPath = $file->store('library-pdfs', 'public');
                $validated['file_path'] = $newPath;
                $validated['file_original_name'] = $file->getClientOriginalName();
                $validated['file_mime'] = $file->getMimeType() ?: 'application/pdf';
                $validated['file_size'] = $file->getSize();
            } else {
                if ($material && $material->hasFile()) {
                    unset($validated['file_path'], $validated['file_original_name'], $validated['file_mime'], $validated['file_size']);
                } else {
                    $validated['file_path'] = null;
                    $validated['file_original_name'] = null;
                    $validated['file_mime'] = null;
                    $validated['file_size'] = null;
                }
            }
            unset($validated['pdf_file']);
        } elseif ($type === LearningMaterialType::LinkOnly->value) {
            $validated['content'] = null;
            $validated['file_path'] = null;
            $validated['file_original_name'] = null;
            $validated['file_mime'] = null;
            $validated['file_size'] = null;
            unset($validated['pdf_file']);
        } else {
            unset($validated['pdf_file']);
        }
    }

    private function isManager($user): bool
    {
        return in_array($user->role, ['admin', 'admin_library', 'pemilik'], true);
    }
}
