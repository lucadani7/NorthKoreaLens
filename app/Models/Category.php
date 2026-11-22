<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    public const PROPAGANDA = 'propaganda';

    protected $fillable = [
        'slug',
        'sort_order',
    ];

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** Translated display name, e.g. "Știri" in Romanian for the "news" slug. */
    public function name(): string
    {
        return __("categories.{$this->slug}");
    }

    public function isPropaganda(): bool
    {
        return $this->slug === self::PROPAGANDA;
    }
}
