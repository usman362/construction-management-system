<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeployToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('deploy.token', '');
        if ($token === '') {
            abort(503, 'Deploy routes are disabled. Set DEPLOY_TOKEN in .env.');
        }

        $given = $request->header('X-Deploy-Token')
            ?? $request->query('token')
            ?? $request->input('token');

        if (! is_string($given) || $given === '' || ! hash_equals($token, $given)) {
            abort(403, 'Invalid or missing deploy token.');
        }

        $allowed = config('deploy.allowed_ips');
        if (is_string($allowed) && $allowed !== '') {
            $ips = array_values(array_filter(array_map('trim', explode(',', $allowed))));
            if ($ips !== [] && ! in_array($request->ip(), $ips, true)) {
                abort(403, 'IP address not allowed for deploy.');
            }
        }

        return $next($request);
    }
}
