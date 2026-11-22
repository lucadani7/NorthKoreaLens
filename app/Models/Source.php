<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SourceType $type
 * @property string $website_url
 * @property string|null $feed_url
 * @property string $language
 * @property bool $is_active
 * @property Carbon|null $last_fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Source extends Model
{
    /** @use HasFactory<SourceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'website_url',
        'feed_url',
        'language',
        'is_active',
        'last_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SourceType::class,
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Active sources that have an RSS feed we can fetch.
     *
     * @param  Builder<Source>  $query
     */
    public function scopeFetchable(Builder $query): void
    {
        $query->where('is_active', true)->whereNotNull('feed_url');
    }
}
