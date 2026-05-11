<?php

namespace App\Http\Middleware;

use App\Support\Translation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?: $request->cookie('locale')
            ?: 'es';

        if (!in_array($locale, Translation::LOCALES, true)) {
            $locale = 'es';
        }

        App::setLocale($locale);

        $response = $next($request);

        return $response->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365));
    }
}
