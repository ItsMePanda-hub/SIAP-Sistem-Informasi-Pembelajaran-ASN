<?php

namespace App\Models;

use App\Enums\LearningLicenseType;
use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LearningMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
        'file_path',
        'file_original_name',
        'file_mime',
        'file_size',
        'material_type',
        'scope',
        'learning_level_id',
        'learning_category_id',
        'publication_status',
        'verification_status',
        'validity_status',
        'published_at',
        'uploaded_by',
    ];

    protected $casts = [
        'scope' => LearningMaterialScope::class,
        'material_type' => LearningMaterialType::class,
        'publication_status' => LearningPublicationStatus::class,
        'verification_status' => LearningVerificationStatus::class,
        'validity_status' => LearningValidityStatus::class,
        'published_at' => 'datetime',
    ];

    public function level()
    {
        return $this->belongsTo(LearningLevel::class, 'learning_level_id');
    }

    public function category()
    {
        return $this->belongsTo(LearningCategory::class, 'learning_category_id');
    }

    public function source()
    {
        return $this->hasOne(LearningMaterialSource::class);
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function libraryBookmarks()
    {
        return $this->hasMany(LibraryBookmark::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function lifecycleLogs()
    {
        return $this->hasMany(LearningMaterialLifecycleLog::class)->latest('id');
    }

    public function scopePublished($query)
    {
        return $query->where('publication_status', LearningPublicationStatus::Published->value);
    }

    public function scopeCore($query)
    {
        return $query->where('scope', LearningMaterialScope::Core->value);
    }

    public function scopeCustom($query)
    {
        return $query->where('scope', LearningMaterialScope::Custom->value);
    }

    public function isPublished(): bool
    {
        return $this->publication_status === LearningPublicationStatus::Published;
    }

    public function isCore(): bool
    {
        return $this->scope === LearningMaterialScope::Core;
    }

    public function isCustom(): bool
    {
        return $this->scope === LearningMaterialScope::Custom;
    }

    public function isNative(): bool
    {
        return $this->material_type === LearningMaterialType::Native;
    }

    public function isPdf(): bool
    {
        return $this->material_type === LearningMaterialType::Pdf;
    }

    public function isLinkOnly(): bool
    {
        return $this->material_type === LearningMaterialType::LinkOnly;
    }

    public function hasContent(): bool
    {
        return trim((string) $this->content) !== '';
    }

    public function hasFile(): bool
    {
        return trim((string) $this->file_path) !== '';
    }

    public function hasProvenance(): bool
    {
        $source = $this->relationLoaded('source') ? $this->source : $this->source()->first();
        if (! $source) {
            return false;
        }

        return trim((string) $source->source_name) !== '' && trim((string) $source->publisher) !== '';
    }

    public function hasApprovedRights(): bool
    {
        $source = $this->relationLoaded('source') ? $this->source : $this->source()->first();
        if (! $source || ! $source->isRightsApproved()) {
            return false;
        }

        // Self-approval prevention: if uploader is known, reviewer cannot be uploader
        if ($this->uploaded_by !== null && (int) $source->rights_reviewed_by === (int) $this->uploaded_by) {
            return false;
        }

        $license = $source->license_type instanceof LearningLicenseType
            ? $source->license_type
            : LearningLicenseType::tryFrom((string) $source->license_type);

        if (! $license || $license === LearningLicenseType::TidakDiketahui) {
            return false;
        }

        // Native or PDF redistributes files: license must be redistributable (cc-by, cc-by-sa, izin_tertulis)
        if ($this->isNative() || $this->isPdf()) {
            if (! $license->isRedistributable()) {
                return false;
            }
            if ($license->requiresEvidence() && empty(trim((string) $source->license_url))) {
                return false;
            }
        } elseif ($this->isLinkOnly()) {
            // Link-only requires valid source_url
            if (empty(trim((string) $source->source_url))) {
                return false;
            }
            if ($license->requiresEvidence() && empty(trim((string) $source->license_url))) {
                return false;
            }
        }

        return true;
    }

    public function isCurrent(): bool
    {
        if (! $this->isPublished()) {
            return false;
        }

        if ($this->isCore()) {
            return $this->verification_status === LearningVerificationStatus::Verified
                && $this->validity_status === LearningValidityStatus::Active
                && $this->hasProvenance()
                && $this->hasApprovedRights();
        }

        return true;
    }

    public function scopeCurrent($query)
    {
        return $query->where('publication_status', LearningPublicationStatus::Published->value)
            ->where(function ($q) {
                $q->where('scope', LearningMaterialScope::Custom->value)
                    ->orWhere(function ($qq) {
                        $qq->where('scope', LearningMaterialScope::Core->value)
                            ->where('verification_status', LearningVerificationStatus::Verified->value)
                            ->where('validity_status', LearningValidityStatus::Active->value)
                            ->where(function ($core) {
                                $core->where(function ($np) {
                                    $np->whereIn('material_type', [LearningMaterialType::Native->value, LearningMaterialType::Pdf->value])
                                        ->whereHas('source', function ($s) {
                                            $s->whereNotNull('source_name')->whereRaw("TRIM(source_name) != ''")
                                                ->whereNotNull('publisher')->whereRaw("TRIM(publisher) != ''")
                                                ->where('rights_status', 'approved')
                                                ->whereNotNull('rights_reviewed_by')
                                                ->whereNotNull('rights_reviewed_at')
                                                ->where(function ($sq) {
                                                    $sq->whereNull('learning_materials.uploaded_by')
                                                        ->orWhereColumn('learning_material_sources.rights_reviewed_by', '!=', 'learning_materials.uploaded_by');
                                                })
                                                ->whereIn('license_type', [LearningLicenseType::CcBy40->value, LearningLicenseType::CcBySa40->value, LearningLicenseType::IzinTertulis->value])
                                                ->whereNotNull('license_url')->whereRaw("TRIM(license_url) != ''");
                                        });
                                })->orWhere(function ($lo) {
                                    $lo->where('material_type', LearningMaterialType::LinkOnly->value)
                                        ->whereHas('source', function ($s) {
                                            $s->whereNotNull('source_name')->whereRaw("TRIM(source_name) != ''")
                                                ->whereNotNull('publisher')->whereRaw("TRIM(publisher) != ''")
                                                ->where('rights_status', 'approved')
                                                ->whereNotNull('rights_reviewed_by')
                                                ->whereNotNull('rights_reviewed_at')
                                                ->where(function ($sq) {
                                                    $sq->whereNull('learning_materials.uploaded_by')
                                                        ->orWhereColumn('learning_material_sources.rights_reviewed_by', '!=', 'learning_materials.uploaded_by');
                                                })
                                                ->whereNotNull('license_type')->whereRaw("TRIM(license_type) != ''")
                                                ->where('license_type', '!=', LearningLicenseType::TidakDiketahui->value)
                                                ->whereNotNull('source_url')->whereRaw("TRIM(source_url) != ''")
                                                ->where(function ($lq) {
                                                    $lq->whereNotIn('license_type', [LearningLicenseType::CcBy40->value, LearningLicenseType::CcBySa40->value, LearningLicenseType::CcByNcSa->value, LearningLicenseType::IzinTertulis->value])
                                                        ->orWhere(function ($ev) {
                                                            $ev->whereIn('license_type', [LearningLicenseType::CcBy40->value, LearningLicenseType::CcBySa40->value, LearningLicenseType::CcByNcSa->value, LearningLicenseType::IzinTertulis->value])
                                                                ->whereNotNull('license_url')->whereRaw("TRIM(license_url) != ''");
                                                        });
                                                });
                                        });
                                });
                            });
                    });
            });
    }

    public function publish(): void
    {
        if ($this->isCore() && ! $this->canCoreBePublished()) {
            throw ValidationException::withMessages([
                'publication_status' => ['Core material hanya boleh published jika provenance lengkap, verification verified, validity active, dan persetujuan hak cipta (rights review) telah disetujui.'],
            ]);
        }

        $this->publication_status = LearningPublicationStatus::Published;
        if (is_null($this->published_at)) {
            $this->published_at = now();
        }
        $this->save();
    }

    public function unpublish(): void
    {
        $this->publication_status = LearningPublicationStatus::Unpublished;
        $this->save();
    }

    public function canCoreBePublished(): bool
    {
        return $this->verification_status === LearningVerificationStatus::Verified
            && $this->validity_status === LearningValidityStatus::Active
            && $this->hasProvenance()
            && $this->hasApprovedRights();
    }

    public array $pendingLifecycleLogs = [];

    private const LIFECYCLE_AUDITED = [
        'publication_status',
        'verification_status',
        'validity_status',
        'scope',
        'material_type',
        'learning_level_id',
        'learning_category_id',
        'title',
        'file_path',
    ];

    private static function lifecycleValue(mixed $v): ?string
    {
        if ($v instanceof \BackedEnum) {
            return (string) $v->value;
        }
        if ($v === null) {
            return null;
        }
        return (string) $v;
    }

    protected static function booted(): void
    {
        static::deleted(function (self $material) {
            if (! empty($material->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($material->file_path);
            }
        });

        static::updating(function (self $material) {
            $dirty = $material->getDirty();
            $audited = array_intersect(array_keys($dirty), self::LIFECYCLE_AUDITED);
            if (empty($audited)) {
                return;
            }
            $pending = [];
            foreach ($audited as $field) {
                $old = $material->getOriginal($field);
                $new = $material->getAttribute($field);
                $oldStr = self::lifecycleValue($old);
                $newStr = self::lifecycleValue($new);
                if ($oldStr === $newStr) {
                    continue;
                }
                if ($field === 'file_path' && trim((string) $oldStr) === '' && trim((string) $newStr) === '') {
                    continue;
                }
                $pending[] = [
                    'field' => $field,
                    'old_value' => $oldStr,
                    'new_value' => $newStr,
                ];
            }
            $material->pendingLifecycleLogs = $pending;
        });

        static::updated(function (self $material) {
            if (empty($material->pendingLifecycleLogs)) {
                return;
            }
            $actorId = Auth::id();
            $titleSnap = (string) ($material->title ?? $material->getOriginal('title') ?? '');
            $slugSnap = (string) ($material->slug ?? $material->getOriginal('slug') ?? '');
            foreach ($material->pendingLifecycleLogs as $entry) {
                LearningMaterialLifecycleLog::create([
                    'learning_material_id' => $material->id,
                    'title_snapshot' => mb_substr($titleSnap, 0, 255),
                    'slug_snapshot' => mb_substr($slugSnap, 0, 255),
                    'actor_user_id' => $actorId,
                    'field' => $entry['field'],
                    'old_value' => $entry['old_value'],
                    'new_value' => $entry['new_value'],
                    'reason' => null,
                    'is_system' => false,
                ]);
            }
            $material->pendingLifecycleLogs = [];
        });

        static::saving(function (self $material) {
            if (empty($material->slug) && ! empty($material->title)) {
                $material->slug = static::generateUniqueSlug($material->title, $material->id);
            } elseif (! empty($material->slug)) {
                $material->slug = Str::slug($material->slug);
                $base = $material->slug;
                $suffix = 1;
                while (static::where('slug', $material->slug)->where('id', '!=', $material->id ?? 0)->exists()) {
                    $material->slug = $base . '-' . $suffix++;
                }
            }

            if ($material->isCore() && $material->publication_status === LearningPublicationStatus::Published) {
                if ($material->verification_status !== LearningVerificationStatus::Verified) {
                    throw ValidationException::withMessages([
                        'publication_status' => ['Core material tidak boleh published apabila belum verified.'],
                    ]);
                }
                if ($material->validity_status !== LearningValidityStatus::Active) {
                    throw ValidationException::withMessages([
                        'publication_status' => ['Core material hanya boleh published jika validity active.'],
                    ]);
                }
                $source = $material->relationLoaded('source') ? $material->source : $material->source()->first();
                $hasProvenance = $source && trim((string) $source->source_name) !== '' && trim((string) $source->publisher) !== '';
                if (! $hasProvenance) {
                    throw ValidationException::withMessages([
                        'publication_status' => ['Core material wajib memiliki provenance (source_name dan publisher).'],
                    ]);
                }
                if (! $material->hasApprovedRights()) {
                    throw ValidationException::withMessages([
                        'publication_status' => ['Core material wajib memiliki persetujuan hak cipta / hak redistribusi yang valid (rights-review approved).'],
                    ]);
                }
            }

            if ($material->publication_status === LearningPublicationStatus::Published) {
                if (is_null($material->published_at)) {
                    $material->published_at = now();
                }
            } else {
                $material->published_at = null;
            }
        });
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
