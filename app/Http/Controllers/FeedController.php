<?php

namespace App\Http\Controllers;

use App\Data\CategoryData;
use App\Data\PaginationData;
use App\Data\PostData;
use App\Models\Category;
use App\Models\Post;
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

        $posts = Post::feed()
            ->with(['source', 'category'])
            ->when($category?->isPropaganda(), fn ($query) => $query->propaganda())
            ->when($category && ! $category->isPropaganda(), fn ($query) => $query->whereBelongsTo($category))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('feed', [
            'posts' => PostData::collect($posts->getCollection()),
            'pagination' => PaginationData::fromPaginator($posts),
            'categories' => CategoryData::collect($categories),
            'activeCategory' => $category?->slug,
            // Server time, so relative dates render the same on the server and in the browser.
            'now' => now()->toIso8601String(),
        ]);
    }
}
