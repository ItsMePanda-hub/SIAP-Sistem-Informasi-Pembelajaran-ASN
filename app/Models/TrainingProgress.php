<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingProgress extends Model
{
    protected $table = 'training_progresses';

    protected $fillable = ['training_id', 'user_id', 'status', 'completed_at', 'certificate_code'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
