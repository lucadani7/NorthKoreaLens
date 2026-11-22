<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the signed-in user's language first, then the visitor's
 * saved choice, then their browser's preferred languages, then the app default.
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

        /** @var array<string, string> $aliases */
        $aliases = config('locales.aliases', []);

        // The browser's languages, most preferred first: "ro-RO" counts as "ro", "no" as "nb".
        foreach ($request->getLanguages() as $language) {
            $code = strtolower(explode('_', str_replace('-', '_', $language))[0]);
            $code = $aliases[$code] ?? $code;

            if (in_array($code, $supported, true)) {
                return $code;
            }
        }

        return config('app.locale');
    }
}
