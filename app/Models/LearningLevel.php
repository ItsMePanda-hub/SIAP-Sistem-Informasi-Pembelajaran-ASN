<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LearningLevel extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'slug', 'description', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $level) {
            if (empty($level->slug) && ! empty($level->name)) {
                $level->slug = static::generateUniqueSlug($level->name, $level->id);
            } elseif (! empty($level->slug)) {
                $level->slug = Str::slug($level->slug);
                $base = $level->slug;
                $suffix = 1;
                while (static::where('slug', $level->slug)->where('id', '!=', $level->id ?? 0)->exists()) {
                    $level->slug = $base . '-' . $suffix++;
                }
            }
        });
    }

    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
