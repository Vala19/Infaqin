<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('infaqin.locales')), 404);

        // Remember the choice for a year.
        Cookie::queue(SetLocale::COOKIE, $locale, 60 * 24 * 365);

        return redirect()->back(fallback: route('landing'));
    }
}
