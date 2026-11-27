<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\SourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property SourceType $type
 * @property int|null $category_id Section for its posts; null means the default for the type
 * @property string $website_url
 * @property string|null $logo_path
 * @property string|null $feed_url
 * @property string $feed_format "feed" (RSS or Atom), "ghost" (Ghost RSS and archive), or "rodong"
 * @property int $fetch_every_hours Hours between two fetches
 * @property list<string>|null $keywords Import only items that mention one of these
 * @property bool $needs_review Import as drafts, to be published by hand
 * @property bool $forbids_ai The source forbids AI tools on its content; no AI features on its posts
 * @property bool $skip_shorts YouTube channels: import regular videos only, no Shorts
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

    protected static function booted(): void
    {
        static::saved(function (Source $source): void {
            if ($source->wasChanged('type')) {
                Post::where('source_id', $source->getKey())
                    ->chunkById((int) config('scout.chunk.searchable', 500), function ($posts): void {
                        foreach ($posts as $post) {
                            $post->searchable();
                        }
                    });
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'type',
        'category_id',
        'website_url',
        'logo_path',
        'feed_url',
        'feed_format',
        'fetch_every_hours',
        'keywords',
        'needs_review',
        'forbids_ai',
        'skip_shorts',
        'language',
        'is_active',
        'last_fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SourceType::class,
            'is_active' => 'boolean',
            'fetch_every_hours' => 'integer',
            'keywords' => 'array',
            'needs_review' => 'boolean',
            'forbids_ai' => 'boolean',
            'skip_shorts' => 'boolean',
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

    /**
     * A notice readers see on every post from this source, in their language, e.g. who a
     * YouTuber is and what is known about them. Written in lang/<locale>/source_notices.php.
     */
    public function notice(): ?string
    {
        $key = "source_notices.{$this->slug}";
        $notice = Lang::has($key) ? __($key) : null;

        // __() gives an array for a key that names a group of translations, not one text.
        return is_string($notice) ? $notice : null;
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path === null) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->logo_path);
    }

    /** Whether the hourly run should fetch it now: never fetched, or its interval has passed. */
    public function isDue(): bool
    {
        return $this->last_fetched_at === null
            || $this->last_fetched_at->lte(now()->subHours(max(1, $this->fetch_every_hours))->addMinutes(5));
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
