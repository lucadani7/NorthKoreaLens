<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Topic extends Model
{
    protected $fillable = ['slug', 'sort_order'];

    /** @return BelongsToMany<Post, $this> */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /** Translated display name, e.g. "Istorie" in Romanian for the "history" slug. */
    public function name(): string
    {
        return __("topics.{$this->slug}");
    }
}
