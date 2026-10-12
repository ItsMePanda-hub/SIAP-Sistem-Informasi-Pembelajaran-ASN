<?php

namespace App\Models;

use App\Enums\LearningLicenseType;
use App\Enums\LearningMaterialType;
use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LearningMaterialSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_material_id',
        'source_name',
        'source_url',
        'source_type',
        'publisher',
        'document_identifier',
        'version',
        'source_issued_at',
        'license_type',
        'license_url',
        'last_checked_at',
        'verification_notes',
        'metadata',
        'rights_status',
        'rights_reviewed_by',
        'rights_reviewed_at',
        'rights_review_notes',
    ];

    protected $casts = [
        'source_issued_at' => 'date',
        'last_checked_at' => 'datetime',
        'rights_reviewed_at' => 'datetime',
        'metadata' => 'array',
        'license_type' => LearningLicenseType::class,
    ];

    public function material()
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }

    public function rightsReviewer()
    {
        return $this->belongsTo(User::class, 'rights_reviewed_by');
    }

    public function rightsLogs()
    {
        return $this->hasMany(LearningMaterialRightsLog::class, 'learning_material_source_id')->latest();
    }

    public function isRightsApproved(): bool
    {
        return $this->rights_status === 'approved'
            && $this->rights_reviewed_at !== null
            && $this->rights_reviewed_by !== null;
    }

    public function recordRightsLog(string $decision, ?int $reviewerId, ?string $notes = null): LearningMaterialRightsLog
    {
        $licenseVal = $this->license_type instanceof LearningLicenseType
            ? $this->license_type->value
            : $this->license_type;

        return $this->rightsLogs()->create([
            'reviewer_user_id' => $reviewerId,
            'decision' => $decision,
            'license_type' => $licenseVal,
            'license_url' => $this->license_url,
            'source_url' => $this->source_url,
            'notes' => $notes,
        ]);
    }

    public $wasAutoInvalidated = false;

    protected static function booted(): void
    {
        static::saving(function (self $source) {
            $material = null;
            if ($source->relationLoaded('material') && $source->material) {
                $material = $source->material;
            } elseif (! empty($source->learning_material_id)) {
                $material = LearningMaterial::find($source->learning_material_id);
            }

            if ($material) {
                $isCore = $material->isCore();
                $isLinkOnly = $material->isLinkOnly();
                $errors = [];
                $needsCoreGate = $isCore && $source->rights_status === 'approved';

                if ($needsCoreGate) {
                    $rawLicense = $source->license_type;
                    $license = $rawLicense instanceof LearningLicenseType ? $rawLicense : LearningLicenseType::tryFrom((string) $rawLicense);
                    if (! $license) {
                        $errors['license_type'] = ['Klasifikasi lisensi wajib untuk materi Core.'];
                    } elseif ($license->requiresEvidence() && trim((string) $source->license_url) === '') {
                        $errors['license_url'] = ['URL bukti lisensi wajib untuk lisensi ini.'];
                    }
                } elseif ($isCore) {
                    $rawLicense = $source->license_type;
                    $license = $rawLicense instanceof LearningLicenseType ? $rawLicense : ($rawLicense !== null && $rawLicense !== '' ? LearningLicenseType::tryFrom((string) $rawLicense) : null);
                    if ($license && $license->requiresEvidence() && trim((string) $source->license_url) === '') {
                        $errors['license_url'] = ['URL bukti lisensi wajib untuk lisensi ini.'];
                    }
                }

                if ($isLinkOnly) {
                    $url = trim((string) $source->source_url);
                    if ($url === '') {
                        $errors['source_url'] = ['URL sumber wajib untuk materi LinkOnly.'];
                    } elseif (! filter_var($url, FILTER_VALIDATE_URL)) {
                        $errors['source_url'] = ['URL sumber tidak valid.'];
                    }
                }

                if (! isset($errors['license_url']) && trim((string) $source->license_url) !== '' && ! filter_var($source->license_url, FILTER_VALIDATE_URL)) {
                    $errors['license_url'] = ['URL bukti lisensi tidak valid.'];
                }
                if (! isset($errors['source_url']) && trim((string) $source->source_url) !== '' && ! filter_var($source->source_url, FILTER_VALIDATE_URL)) {
                    $errors['source_url'] = ['URL sumber tidak valid.'];
                }

                if (! empty($errors)) {
                    throw ValidationException::withMessages($errors);
                }
            }

            $evidenceFields = ['license_type', 'license_url', 'source_url', 'source_name', 'publisher', 'document_identifier'];
            if ($source->exists && $source->isDirty($evidenceFields) && ! $source->isDirty('rights_reviewed_at') && $source->getOriginal('rights_status') !== 'unreviewed') {
                $source->rights_status = 'unreviewed';
                $source->rights_reviewed_by = null;
                $source->rights_reviewed_at = null;
                $source->wasAutoInvalidated = true;
            }
        });

        static::saved(function (self $source) {
            if (! empty($source->wasAutoInvalidated)) {
                $source->recordRightsLog('unreviewed', null, 'Sistem: bukti sumber diubah, persetujuan dibatalkan otomatis.');
                $source->wasAutoInvalidated = false;
            }
        });
    }
}
