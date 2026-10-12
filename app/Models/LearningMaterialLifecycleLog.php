<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningMaterialLifecycleLog extends Model
{
    public $timestamps = false;

    protected $table = 'learning_material_lifecycle_logs';

    protected $fillable = [
        'learning_material_id',
        'title_snapshot',
        'slug_snapshot',
        'actor_user_id',
        'field',
        'old_value',
        'new_value',
        'reason',
        'is_system',
        'created_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
