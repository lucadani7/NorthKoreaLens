<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the signed-in user's language first, then the visitor's
 * saved choice, then the app default (Romanian). The browser's language is ignored on
 * purpose: many readers keep their browser in English and would never notice that
 * NK Lens speaks other languages.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = array_keys(config('locales.supported'));
        $userLocale = $request->user()?->preferredLocale();

        if ($userLocale !== null) {
            return $userLocale;
        }

        $saved = $request->cookie(config('locales.cookie'));

        if (is_string($saved) && in_array($saved, $supported, true)) {
            return $saved;
        }

        return config('app.locale');
    }
}
