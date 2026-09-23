<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use LogsActivity;
{
    protected $fillable = ['title', 'nomor_surat', 'category', 'target_unit_kerja', 'file_path', 'uploaded_by', 'visibility', 'target_role'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Check if a user can access this letter based on visibility rules.
     */
    public function isAccessibleBy(User $user): bool
    {
        // Admin can access everything
        if ($user->role === 'admin') {
            return true;
        }
        
        // Pemilik can access everything (cross-bidang)
        if ($user->role === 'pemilik') {
            return true;
        }
        
        // If no visibility restrictions, fallback to older target_unit_kerja logic or everyone
        if (is_null($this->visibility)) {
            if (!is_null($this->target_unit_kerja)) {
                return $user->unit_kerja === $this->target_unit_kerja;
            }
            return true;
        }
        
        // Public letters
        if ($this->visibility === 'all') {
            return true;
        }
        
        // Unit-specific letters
        if ($this->visibility === 'unit' && !is_null($this->target_unit_kerja)) {
            return $user->unit_kerja === $this->target_unit_kerja;
        }
        
        // Role-specific letters
        if ($this->visibility === 'role' && !is_null($this->target_role)) {
            return $user->role === $this->target_role;
        }
        
        return false;
    }
}
