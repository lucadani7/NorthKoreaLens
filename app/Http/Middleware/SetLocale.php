<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the visitor's saved choice first, then their browser's
 * preferred languages, then the app default.
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
        $saved = $request->cookie(config('locales.cookie'));

        if (is_string($saved) && in_array($saved, $supported, true)) {
            return $saved;
        }

        // getPreferredLanguage() also matches "ro-RO" to "ro"; it falls back to the first entry.
        $preferred = $request->getPreferredLanguage($supported);

        return in_array($preferred, $supported, true) ? $preferred : config('app.locale');
    }
}
