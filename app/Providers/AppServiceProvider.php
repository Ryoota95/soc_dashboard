<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\SecurityDataProvider::class,
            fn () => config('opensearch.driver') === 'opensearch'
                ? new \App\Services\OpenSearchProvider()
                : new \App\Services\DummyDataProvider()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}