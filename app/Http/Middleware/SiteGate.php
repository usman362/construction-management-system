<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site-wide login gate (Ali 2026-06-27).
 *
 * When SITE_GATE_ENABLED=true, every request hits a shared username/password
 * prompt before reaching the app's normal routes. Once entered correctly,
 * a 30-day signed cookie marks the browser as "let through" so the user
 * isn't prompted on every request.
 *
 * Configured via env:
 *   SITE_GATE_ENABLED=true
 *   SITE_GATE_USERNAME=bcr-test
 *   SITE_GATE_PASSWORD=somethinglongandrandom
 *
 * Use for staging / pre-launch protection without configuring .htaccess or
 * cPanel Directory Privacy. Disable in prod once the site is GA by setting
 * SITE_GATE_ENABLED=false (or removing the var).
 */
class SiteGate
{
    private const COOKIE_NAME = 'site_gate_ok';
    private const COOKIE_DAYS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        if (! filter_var(env('SITE_GATE_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return $next($request);
        }

        // Whitelist the health endpoint so cron / load balancers can hit it.
        if ($request->is('up')) {
            return $next($request);
        }

        $expectedUser = (string) env('SITE_GATE_USERNAME', '');
        $expectedPass = (string) env('SITE_GATE_PASSWORD', '');

        // If admin forgot to set credentials, fail closed with a clear message.
        if ($expectedUser === '' || $expectedPass === '') {
            return response(
                'Site gate is enabled but SITE_GATE_USERNAME / SITE_GATE_PASSWORD are not set in .env.',
                503
            );
        }

        // Already passed gate previously?
        $cookie = $request->cookie(self::COOKIE_NAME);
        $expectedToken = hash('sha256', $expectedUser . '|' . $expectedPass);
        if (is_string($cookie) && hash_equals($expectedToken, $cookie)) {
            return $next($request);
        }

        // POST attempt from the gate form
        if ($request->isMethod('post') && $request->input('_gate') === '1') {
            if (
                hash_equals($expectedUser, (string) $request->input('gate_username', ''))
                && hash_equals($expectedPass, (string) $request->input('gate_password', ''))
            ) {
                return redirect($request->input('redirect_to') ?: '/')
                    ->withCookie(Cookie::make(self::COOKIE_NAME, $expectedToken, 60 * 24 * self::COOKIE_DAYS, '/', null, false, true));
            }
            $error = 'Incorrect username or password.';
        }

        return response()->view('gate', [
            'error'      => $error ?? null,
            'redirectTo' => $request->fullUrl(),
        ], 401);
    }
}
