<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_attempt_id',
        'question_id',
        'answer_text',
        'option_id',
        'points_earned',
        'is_correct',
    ];

    /**
     * Get the exam attempt that owns this answer.
     */
    public function examAttempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    /**
     * Get the question that this answer is for.
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the selected option (if multiple choice).
     */
    public function option()
    {
        return $this->belongsTo(Option::class);
    }
}
