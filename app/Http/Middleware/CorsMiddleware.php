<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log semua request untuk debugging
        Log::info('CORS Middleware - Request received', [
            'method' => $request->method(),
            'url' => $request->url(),
            'origin' => $request->header('Origin'),
            'user_agent' => $request->header('User-Agent'),
            'content_type' => $request->header('Content-Type'),
            'all_headers' => $request->headers->all(),
        ]);

        // Get allowed origins from environment
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001'));
        $origin = $request->header('Origin');

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            Log::info('CORS Middleware - Handling OPTIONS preflight');
            $response = response('', 204); // Changed to 204 No Content
        } else {
            Log::info('CORS Middleware - Passing to next middleware');
            try {
                $response = $next($request);
                Log::info('CORS Middleware - Response received from app', [
                    'status' => $response->getStatusCode(),
                ]);
            } catch (\Exception $e) {
                Log::error('CORS Middleware - Exception caught', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        }

        // Set CORS headers
        if ($origin) {
            // Check if origin is allowed
            if (in_array($origin, $allowedOrigins) || $this->isAllowedPattern($origin, $allowedOrigins)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                Log::info('CORS Middleware - Origin allowed', ['origin' => $origin]);
            } else {
                // Untuk debugging production - allow semua origin sementara
                $response->headers->set('Access-Control-Allow-Origin', '*');
                Log::info('CORS Middleware - Origin not in whitelist, allowing all', ['origin' => $origin]);
            }
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            Log::info('CORS Middleware - No origin header, allowing all');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-CSRF-TOKEN, Origin');
        $response->headers->set('Access-Control-Allow-Credentials', 'false'); // Set ke false untuk wildcard origin
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours

        Log::info('CORS Middleware - Headers set, returning response');
        return $response;
    }

    /**
     * Check if origin matches allowed patterns
     */
    private function isAllowedPattern(string $origin, array $allowedOrigins): bool
    {
        foreach ($allowedOrigins as $allowed) {
            // Support wildcard patterns like *.vercel.app
            if (str_contains($allowed, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($allowed, '/'));
                if (preg_match("/^$pattern$/", $origin)) {
                    return true;
                }
            }
        }
        return false;
    }
}
