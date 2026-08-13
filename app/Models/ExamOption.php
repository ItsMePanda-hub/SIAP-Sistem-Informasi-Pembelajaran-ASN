<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamOption extends Model
{
    protected $fillable = ['exam_question_id', 'option_text', 'is_correct'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(ExamQuestion::class, 'exam_question_id');
    }
}
