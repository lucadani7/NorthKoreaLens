<?php

namespace App\Http\Controllers;

use App\Data\PageData;
use App\Data\SourceListingData;
use App\Models\Source;
use Inertia\Inertia;
use Inertia\Response;

/** The Sources page: a short introduction in Markdown and the outlets we take articles from. */
class SourcesController extends Controller
{
    public function __invoke(): Response
    {
        $sources = Source::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('sources', [
            'page' => PageData::load('sources', app()->getLocale()),
            'sources' => SourceListingData::collect($sources),
        ]);
    }
}
