<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
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
