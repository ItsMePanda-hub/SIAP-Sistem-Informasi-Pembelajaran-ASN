<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamViolation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'violation_type',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the exam attempt that owns this violation.
     */
    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }
}
