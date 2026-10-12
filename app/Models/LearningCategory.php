<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LearningCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function materials()
    {
        return $this->hasMany(LearningMaterial::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if (empty($category->slug) && ! empty($category->name)) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            } elseif (! empty($category->slug)) {
                $category->slug = Str::slug($category->slug);
                $base = $category->slug;
                $suffix = 1;
                while (static::where('slug', $category->slug)->where('id', '!=', $category->id ?? 0)->exists()) {
                    $category->slug = $base . '-' . $suffix++;
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
