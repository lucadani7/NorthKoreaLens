<?php

namespace App\Http\Controllers;

use App\Data\PostData;
use App\Models\Post;
use App\Services\PostAudioService;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function show(Post $post, PostAudioService $audio): Response
    {
        // Drafts and scheduled posts are not public yet. Deleted posts never reach here.
        abort_unless($post->isPublished(), 404);

        $locale = app()->getLocale();
        $post->load(['source', 'category', 'topics', 'translations' => fn ($query) => $query->where('locale', $locale)]);
        $translation = $post->translationFor($locale);
        $audioLocale = filled($translation?->body) ? $locale : $post->language;

        // "More on this topic": up to three other published posts that share a topic.
        $related = $post->topics->isEmpty() ? collect() : Post::feed()
            ->whereKeyNot($post->id)
            ->whereHas('topics', fn ($query) => $query->whereKey($post->topics->modelKeys()))
            ->with(['source', 'category', 'topics', 'translations' => fn ($query) => $query->where('locale', $locale)])
            ->limit(3)
            ->get();

        return Inertia::render('post', [
            'post' => PostData::fromModel($post, includeOriginalEditorialNote: true),
            // Full text only exists for content written by the NK Lens team.
            'body' => $post->localized('body', $locale),
            'audioUrl' => $post->isOriginalByNkLens() ? $audio->audioUrlFor($post, $audioLocale) : null,
            'related' => PostData::collect($related),
            // Highlighted in the sections column and the phone menu.
            'activeCategory' => $post->category->slug,
            'now' => now()->toIso8601String(),
        ]);
    }
}
