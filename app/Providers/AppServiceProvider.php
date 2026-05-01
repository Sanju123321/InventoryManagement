<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // Match Bootstrap CSS (Tailwind default uses w-5/h-5 SVG icons — ignored without Tailwind,
        // then global svg { max-width:100% } blows icons up to full container width).
        Paginator::useBootstrapFive();
    }
}
