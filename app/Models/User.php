<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'jabatan',
        'unit_kerja',
        'role',
        'atasan_id',
        'status_kepegawaian',
        'simpeg_synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'simpeg_synced_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function atasan()
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    public function bawahan()
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Admin & pemilik punya visibilitas lintas-bidang; atasan & pegawai cuma bidangnya sendiri.
    public function isSystemWide(): bool
    {
        return in_array($this->role, ['admin', 'pemilik']);
    }
}
