<?php

namespace App\Models;

use App\Enums\SourceType;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'source_id',
        'user_id',
        'category_id',
        'title',
        'excerpt',
        'body',
        'original_url',
        'guid',
        'image_url',
        'language',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
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

    /** Posts that are published, newest first. */
    public function scopeFeed(Builder $query): void
    {
        $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at');
    }

    /** Posts that must carry the propaganda label. */
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
        return $this->category?->isPropaganda()
            || $this->source?->type?->isPropaganda()
            || false;
    }
}
