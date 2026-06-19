<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api-role-based', function (Request $request) {

            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(5)->by($request->ip());
            }

            if ($user->hasRole('admin')) {
                return Limit::perMinute(60)->by($user->id);
            }

            return Limit::perMinute(10)->by($user->id);
        });

    }
}
