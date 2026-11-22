<?php

namespace App\Http\Controllers;

use App\Data\PostData;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function show(Post $post): Response
    {
        // Drafts and scheduled posts are not public yet. Deleted posts never reach here.
        abort_unless($post->isPublished(), 404);

        $post->load(['source', 'category']);

        return Inertia::render('post', [
            'post' => PostData::fromModel($post),
            // Full text only exists for content written by the NK Lens team.
            'body' => $post->body,
            'now' => now()->toIso8601String(),
        ]);
    }
}
