<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'deadline',
        'target_type',
        'target_unit_kerja',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
