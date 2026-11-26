<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A post written in one more language. Empty fields fall back to the post's own text.
 *
 * @property int $id
 * @property int $post_id
 * @property string $locale
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $body
 * @property string|null $editorial_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PostTranslation extends Model
{
    protected $fillable = ['post_id', 'locale', 'title', 'excerpt', 'body', 'editorial_note'];

    protected static function booted(): void
    {
        static::saved(fn (PostTranslation $translation) => $translation->post?->searchable());
        static::deleted(fn (PostTranslation $translation) => $translation->post?->searchable());
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
