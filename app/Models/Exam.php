<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'training_id', 'title', 'description', 'target_unit_kerja',
        'duration_minutes', 'max_violations', 'created_by',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function attemptFor(User $user)
    {
        return $this->attempts()->where('user_id', $user->id)->latest('id')->first();
    }
}
