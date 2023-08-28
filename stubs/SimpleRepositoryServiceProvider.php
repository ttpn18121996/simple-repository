<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SimpleRepositoryServiceProvider extends ServiceProvider
{
    protected $repositories = [
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->repositories as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
