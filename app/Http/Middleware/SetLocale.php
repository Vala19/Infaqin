<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('infaqin.locales'));

        // An explicit choice (cookie) wins; otherwise follow the browser's preferred language.
        $locale = $request->cookie(self::COOKIE);
        if (! in_array($locale, $available, true)) {
            $locale = $request->getPreferredLanguage($available) ?? config('app.locale');
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}
