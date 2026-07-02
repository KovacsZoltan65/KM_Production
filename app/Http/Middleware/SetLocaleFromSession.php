<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        $request->setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supportedLocales = config('app.supported_locales', ['hu', 'en']);
        $fallbackLocale = (string) config('app.locale', 'hu');
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (is_string($sessionLocale) && in_array($sessionLocale, $supportedLocales, true)) {
            return $sessionLocale;
        }

        return in_array($fallbackLocale, $supportedLocales, true) ? $fallbackLocale : 'hu';
    }
}
