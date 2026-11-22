<?php

namespace App\Http\Middleware;

use App\Data\CategoryData;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => app()->getLocale(),
            'locales' => $this->locales(),
            // Interface strings of the current language (lang/{locale}.json), keyed by the English text.
            'translations' => fn () => app('translator')->getLoader()->load(app()->getLocale(), '*', '*'),
            // Feed sections for the phone menu, which is on every public page.
            'sections' => fn () => CategoryData::collect(Category::orderBy('sort_order')->get()),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The interface languages for the language picker, in their own names.
     *
     * @return list<array{code: string, name: string}>
     */
    private function locales(): array
    {
        /** @var array<string, string> $supported */
        $supported = config('locales.supported');

        $locales = [];

        foreach ($supported as $code => $name) {
            $locales[] = ['code' => $code, 'name' => $name];
        }

        return $locales;
    }
}
