<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property int|null $source_id
 * @property int|null $user_id
 * @property int $category_id
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $editorial_note
 * @property string|null $epigraph A short verse or quote shown above the text, in every language
 * @property string|null $original_url
 * @property string|null $guid
 * @property string|null $image_url
 * @property string|null $image_credit Author and licence of the image, shown under it
 * @property string $language
 * @property Carbon|null $published_at
 * @property Carbon|null $source_published_at When the source published it; drafts keep it until published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Source|null $source
 * @property-read Category $category
 * @property-read User|null $author
 * @property-read Collection<int, PostTranslation> $translations
 * @property-read Collection<int, Topic> $topics
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'source_id',
        'user_id',
        'category_id',
        'title',
        'excerpt',
        'body',
        'editorial_note',
        'epigraph',
        'original_url',
        'guid',
        'image_url',
        'image_credit',
        'language',
        'published_at',
        'source_published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'source_published_at' => 'datetime',
        ];
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        // Keep generated fields and embeddings out of the index, including for sources that forbid AI.
        $searchable = [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'editorial_note' => $this->editorial_note,
            'epigraph' => $this->epigraph,
            'language' => $this->language,
        ];

        if (config('scout.driver') !== 'meilisearch') {
            return $searchable;
        }

        $filterDate = $this->source_id === null
            ? $this->published_at
            : ($this->source_published_at ?? $this->published_at);

        return [
            ...$searchable,
            'available_languages' => $this->translations
                ->pluck('locale')
                ->push($this->language)
                ->unique()
                ->values()
                ->all(),
            'category_id' => $this->category_id,
            'source_type' => $this->source?->type->value,
            'search_date' => $filterDate?->getTimestamp(),
        ];
    }

    /**
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with([
            'source:id,type',
            'translations:id,post_id,locale',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    /** @return BelongsTo<Source, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<PostTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(PostTranslation::class);
    }

    /** @return BelongsToMany<Topic, $this> */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class);
    }

    /** The translation into this language, if there is one (uses the loaded relation when present). */
    public function translationFor(string $locale): ?PostTranslation
    {
        if ($locale === $this->language) {
            return null;
        }

        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }

    /** The language the reader gets: theirs when translated (or written in it), otherwise the post's own. */
    public function contentLocale(string $locale): string
    {
        return $locale === $this->language || $this->translationFor($locale) !== null ? $locale : $this->language;
    }

    /**
     * A text field in the reader's language, falling back to the post's own text.
     *
     * @param  'title'|'excerpt'|'body'|'editorial_note'  $field
     */
    public function localized(string $field, string $locale): ?string
    {
        $translated = $this->translationFor($locale)?->{$field};

        return filled($translated) ? $translated : $this->{$field};
    }

    /**
     * Posts that are published, newest first.
     *
     * @param  Builder<Post>  $query
     */
    public function scopeFeed(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            // Posts often share a publication time (a batch published at once, a feed that
            // rounds to the minute). Without a tiebreaker their order is not stable, so the
            // same post could show up on two pages.
            ->latest('id');
    }

    /**
     * Posts that must carry the propaganda label.
     *
     * @param  Builder<Post>  $query
     */
    public function scopePropaganda(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', Category::PROPAGANDA))
                ->orWhereHas('source', fn (Builder $q) => $q->where('type', SourceType::StateMedia));
        });
    }

    /**
     * A post is propaganda if it sits in the propaganda category
     * or comes from North Korean state media, whatever its category.
     */
    public function isPropaganda(): bool
    {
        return $this->category->isPropaganda()
            || ($this->source?->type->isPropaganda() ?? false);
    }

    /** Makes a draft public, with the date the source gave it (or now, when there is none). */
    public function publish(): void
    {
        if ($this->published_at !== null) {
            return;
        }

        $this->published_at = $this->source_published_at ?? Carbon::now();
        $this->save();
    }

    /** Visible to the public: it has a publication date and that date has come. */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }
}
