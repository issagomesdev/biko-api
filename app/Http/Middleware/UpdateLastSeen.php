<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($user = $request->user()) {
            $cacheKey = "user_last_seen_{$user->id}";

            if (! Cache::has($cacheKey)) {
                $user->timestamps = false;
                $user->update(['last_seen_at' => now()]);
                $user->timestamps = true;

                Cache::put($cacheKey, true, 60);
            }
        }

        return $response;
    }
}
