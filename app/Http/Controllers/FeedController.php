<?php

namespace App\Http\Controllers;

use App\Data\CategoryData;
use App\Data\PaginationData;
use App\Data\PostData;
use App\Data\TopicData;
use App\Models\Category;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public const PER_PAGE = 20;

    public function __invoke(Request $request): Response
    {
        $categories = Category::orderBy('sort_order')->get();

        $slug = $request->string('category')->toString() ?: null;
        $category = $slug ? $categories->firstWhere('slug', $slug) : null;

        abort_if($slug !== null && $category === null, 404);

        // A topic only filters inside a section (e.g. Curiosities → History).
        $topicSlug = $category ? ($request->string('topic')->toString() ?: null) : null;
        $topic = $topicSlug ? Topic::where('slug', $topicSlug)->first() : null;

        abort_if($topicSlug !== null && $topic === null, 404);

        $locale = app()->getLocale();

        $posts = Post::feed()
            ->with(['source', 'category', 'topics', 'translations' => fn ($query) => $query->where('locale', $locale)])
            ->when($topic, fn ($query) => $query->whereHas('topics', fn ($q) => $q->whereKey($topic?->id)))
            ->when($category?->isPropaganda(), fn ($query) => $query->propaganda())
            ->when($category && ! $category->isPropaganda(), fn ($query) => $query->whereBelongsTo($category))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('feed', [
            'posts' => PostData::collect($posts->getCollection()),
            'pagination' => PaginationData::fromPaginator($posts),
            'categories' => CategoryData::collect($categories),
            'activeCategory' => $category?->slug,
            // The topics that have published posts in this section, for the filter chips.
            'topics' => $category
                ? TopicData::collect(Topic::query()
                    ->whereHas('posts', fn ($query) => $query->feed()->whereBelongsTo($category))
                    ->orderBy('sort_order')
                    ->get())
                : [],
            'activeTopic' => $topic?->slug,
            // Server time, so relative dates render the same on the server and in the browser.
            'now' => now()->toIso8601String(),
        ]);
    }
}
