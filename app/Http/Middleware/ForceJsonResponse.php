<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->fullUrl(), '/api')) {
            $request->headers->set('Accept', 'application/json');

            $userAgent = $request->header('User-Agent');

            Log::warning('WebView access attempt', [
                'user_agent' => $userAgent,
            ]);

            // Check User-Agent
            if (preg_match('/wv|webview|androidwebview/i', $userAgent)) {

                return response()->json([
                    'message' => 'Access denied: This API cannot be accessed from a WebView app.',
                ], 403);
            }

            return $next($request);
        }

        return $next($request);
    }
}
