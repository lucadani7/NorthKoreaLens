<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /** Remembers the language the visitor picked: in a cookie for a year, and in the account when signed in. */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('locales.supported')))],
        ]);

        Cookie::queue(config('locales.cookie'), $validated['locale'], 60 * 24 * 365);

        $request->user()?->update(['locale' => $validated['locale']]);

        return back();
    }
}
