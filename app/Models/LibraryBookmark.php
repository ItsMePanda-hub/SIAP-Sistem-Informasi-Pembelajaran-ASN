<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBookmark extends Model
{
    protected $table = 'library_bookmarks';

    protected $fillable = [
        'user_id',
        'learning_material_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function learningMaterial()
    {
        return $this->belongsTo(LearningMaterial::class);
    }
}
