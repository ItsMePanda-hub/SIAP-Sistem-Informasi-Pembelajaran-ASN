<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    protected $table = 'reading_progress';

    protected $fillable = [
        'user_id',
        'learning_material_id',
        'progress_percent',
        'started_at',
        'last_read_at',
        'completed_at',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'started_at' => 'datetime',
        'last_read_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    public function isCompleted(): bool
    {
        return (int) $this->progress_percent >= 100;
    }
}
