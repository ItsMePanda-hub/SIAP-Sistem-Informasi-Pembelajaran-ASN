<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'type',
        'question_text',
        'points',
        'order',
    ];

    /**
     * Get the exam that owns this question.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the options for this question (if multiple choice).
     */
    public function options()
    {
        return $this->hasMany(Option::class);
    }

    /**
     * Check if this is a multiple choice question.
     */
    public function isMultipleChoice(): bool
    {
        return $this->type === 'multiple_choice';
    }

    /**
     * Check if this is an essay question.
     */
    public function isEssay(): bool
    {
        return $this->type === 'essay';
    }

    /**
     * Get the correct answer for this question.
     */
    public function getCorrectAnswer()
    {
        if ($this->isMultipleChoice()) {
            return $this->options()->where('is_correct', true)->first();
        }
        
        return null; // Essay questions don't have predefined correct answers
    }
}
