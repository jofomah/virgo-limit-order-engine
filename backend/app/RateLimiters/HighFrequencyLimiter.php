<?php

namespace App\RateLimiters;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HighFrequencyLimiter
{
    /**
     * Handle the rate limiting for the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<\Illuminate\Cache\RateLimiting\Limit>
     */
    public function __invoke(Request $request): array
    {
        // Define the limit based on the authentication status
        $limit = Auth::check()
            ? Limit::perSecond(120)->by($request->user()->id) // 120 RPS for authenticated user
            : Limit::perMinute(60)->by($request->ip());      // 60 RPM for guest IP
            
        // Return the limit wrapped in an array, which is required by RateLimiter::for()
        return [
            $limit->response(function (Request $request, array $headers) {
                return response('Too many requests. Please slow down your trading.', 429, $headers);
            })
        ];
    }
}
