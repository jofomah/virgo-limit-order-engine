<?php

use App\Exceptions\AppDomainException;
use App\RateLimiters\HighFrequencyLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',

        health: '/up',
        using: function () {
            // 1. Define the Rate Limiter
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            RateLimiter::for('broadcast', function (Request $request) {
                return Limit::perSecond(30)->by(
                    $request->user()?->id ?: $request->ip()
                );
            });

            RateLimiter::for('high_frequency', function (Request $request) {
                // Instantiate the class and call its __invoke method
                return app(HighFrequencyLimiter::class)->__invoke($request);
            });

            // 2. Load the Routes (Which use the throttle rule)
            Route::middleware(['api'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AppDomainException $e) {
            return $e->toResponse();
        });

        $exceptions->renderable(function (AccessDeniedHttpException $e, $request) {
            return response()->json([
                'error'    => 'FORBIDDEN_ACCESS',
                'message'  => 'You do not have permission to cancel this order.',
                'order_id' => $request->route('id'),
            ], Response::HTTP_FORBIDDEN);
        });

    })->create();
