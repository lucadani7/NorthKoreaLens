<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SourceType $type
 * @property int|null $category_id Section for its posts; null means the default for the type
 * @property string $website_url
 * @property string|null $feed_url
 * @property list<string>|null $keywords Import only items that mention one of these
 * @property bool $needs_review Import as drafts, to be published by hand
 * @property bool $forbids_ai The source forbids AI tools on its content; no AI features on its posts
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
        'category_id',
        'website_url',
        'feed_url',
        'keywords',
        'needs_review',
        'forbids_ai',
        'language',
        'is_active',
        'last_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SourceType::class,
            'is_active' => 'boolean',
            'keywords' => 'array',
            'needs_review' => 'boolean',
            'forbids_ai' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** The section imported posts land in: the one chosen for this source, or the default for its type. */
    public function importCategory(): Category
    {
        return $this->category
            ?? Category::where('slug', $this->type->defaultCategorySlug())->firstOrFail();
    }

    /**
     * Whether an item belongs on NK Lens. Without keywords every item does; with keywords,
     * the text must contain one of them at the start of a word, ignoring case
     * ("North Korea" also matches "North Korean").
     */
    public function wants(string $text): bool
    {
        $keywords = array_filter(array_map('trim', $this->keywords ?? []));

        if ($keywords === []) {
            return true;
        }

        foreach ($keywords as $keyword) {
            if (preg_match('/(?<![\\p{L}\\p{N}])'.preg_quote($keyword, '/').'/iu', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /** The channel id when the feed is a YouTube channel feed (feeds/videos.xml?channel_id=…). */
    public function youtubeChannelId(): ?string
    {
        if ($this->feed_url === null || ! str_starts_with($this->feed_url, 'https://www.youtube.com/feeds/videos.xml')) {
            return null;
        }

        parse_str((string) parse_url($this->feed_url, PHP_URL_QUERY), $query);
        $id = $query['channel_id'] ?? null;

        return is_string($id) && preg_match('/\\AUC[A-Za-z0-9_-]{22}\\z/', $id) === 1 ? $id : null;
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
