<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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
    Schema::defaultStringLength(191);

    // Add the route loading logic here:
    Route::middleware('api') // This applies the 'api' middleware group (rate limiting, etc.)
        ->prefix('api') // This adds the '/api' prefix to all routes in the file
        ->group(base_path('routes/api.php'));
        
    // If you plan to use web routes for email verification success pages,
    // you might also need this (though less critical for a pure API):
    /*
    Route::middleware('web')
        ->group(base_path('routes/web.php'));
    */
}
}
