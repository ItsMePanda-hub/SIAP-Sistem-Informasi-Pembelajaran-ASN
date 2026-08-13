<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id', 'exam_question_id', 'exam_option_id',
        'essay_answer', 'essay_graded_correct',
    ];

    protected $casts = [
        'essay_graded_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }

    public function option()
    {
        return $this->belongsTo(ExamOption::class, 'exam_option_id');
    }
}
