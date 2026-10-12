<?php

namespace App\Models;

use App\Enums\LearningLicenseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningMaterialRightsLog extends Model
{
    use HasFactory;

    protected $table = 'learning_material_rights_logs';

    protected $fillable = [
        'learning_material_source_id',
        'reviewer_user_id',
        'decision',
        'license_type',
        'license_url',
        'source_url',
        'notes',
    ];

    protected $casts = [
        'license_type' => LearningLicenseType::class,
    ];

    public function source()
    {
        return $this->belongsTo(LearningMaterialSource::class, 'learning_material_source_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
