<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use LogsActivity;
{
    protected $fillable = ['title', 'description', 'material_path', 'target_unit_kerja', 'created_by'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progresses()
    {
        return $this->hasMany(TrainingProgress::class);
    }

    public function progressFor(User $user)
    {
        return $this->progresses()->where('user_id', $user->id)->first();
    }

    public function completionPercentage(): int
    {
        $totalPegawai = User::where('role', 'pegawai')
            ->where('unit_kerja', $this->creator->unit_kerja)
            ->count();

        if ($totalPegawai === 0) {
            return 0;
        }

        $selesai = $this->progresses()
            ->where('status', 'selesai')
            ->whereHas('user', fn ($q) => $q->where('role', 'pegawai'))
            ->count();

        return (int) round(($selesai / $totalPegawai) * 100);
    }
}
