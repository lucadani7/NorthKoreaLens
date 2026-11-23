<?php

namespace App\Http\Controllers;

use App\Data\CategoryData;
use App\Data\PaginationData;
use App\Data\PostData;
use App\Data\TopicData;
use App\Models\Category;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public const PER_PAGE = 20;

    public function __invoke(Request $request): Response|RedirectResponse
    {
        // The home page is the News section, so /?category=news has one address: /.
        if ($request->query('category') === Category::DEFAULT) {
            return redirect()->route('home', $request->except('category'), 301);
        }

        $categories = Category::orderBy('sort_order')->get();

        $slug = $request->string('category')->toString() ?: Category::DEFAULT;
        $category = $categories->firstWhere('slug', $slug);

        if ($category === null) {
            abort(404);
        }

        // A topic filters inside the section (e.g. Curiosities → History).
        $topicSlug = $request->string('topic')->toString() ?: null;
        $topic = $topicSlug ? Topic::where('slug', $topicSlug)->first() : null;

        abort_if($topicSlug !== null && $topic === null, 404);

        $locale = app()->getLocale();

        $posts = Post::feed()
            ->with(['source', 'category', 'topics', 'translations' => fn ($query) => $query->where('locale', $locale)])
            ->when($topic, fn ($query) => $query->whereHas('topics', fn ($q) => $q->whereKey($topic?->id)))
            ->when($category->isPropaganda(), fn ($query) => $query->propaganda())
            ->when(! $category->isPropaganda(), fn ($query) => $query->whereBelongsTo($category))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('feed', [
            'posts' => PostData::collect($posts->getCollection()),
            'pagination' => PaginationData::fromPaginator($posts),
            'categories' => CategoryData::collect($categories),
            'activeCategory' => $category->slug,
            // The topics that have published posts in this section, for the filter chips.
            'topics' => TopicData::collect(Topic::query()
                ->whereHas('posts', fn ($query) => $query->feed()->whereBelongsTo($category))
                ->orderBy('sort_order')
                ->get()),
            'activeTopic' => $topic?->slug,
            // Server time, so relative dates render the same on the server and in the browser.
            'now' => now()->toIso8601String(),
        ]);
    }
}
