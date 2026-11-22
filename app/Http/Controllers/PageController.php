<?php

namespace App\Http\Controllers;

use App\Data\PageData;
use Inertia\Inertia;
use Inertia\Response;

/** Pages the team writes in Markdown, such as About us and the editorial policy. */
class PageController extends Controller
{
    public function __invoke(string $page): Response
    {
        $content = PageData::load($page, app()->getLocale());

        abort_if($content === null, 404);

        return Inertia::render('page', [
            'page' => $content,
        ]);
    }
}
