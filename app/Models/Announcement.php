<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'category', 'target_unit_kerja', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function readPercentage(): int
    {
        $totalPegawai = User::where('role', 'pegawai')
            ->where('unit_kerja', $this->creator->unit_kerja)
            ->count();

        if ($totalPegawai === 0) {
            return 0;
        }

        $sudahBaca = $this->reads()
            ->whereHas('user', fn ($q) => $q->where('role', 'pegawai'))
            ->count();

        return (int) round(($sudahBaca / $totalPegawai) * 100);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }
}
