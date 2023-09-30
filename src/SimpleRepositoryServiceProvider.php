<?php

namespace SimpleRepository;

use Illuminate\Support\ServiceProvider;
use SimpleRepository\Console\Commands\MakeRepository;
use SimpleRepository\Console\Commands\MakeService;

class SimpleRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/simple-repository.php',
            'simple-repository'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configurePublishing();
    }

    /**
     * Configure the publishable resources offered by the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/SimpleRepositoryServiceProvider.php' => $this->app->basePath('app/Providers/SimpleRepositoryServiceProvider.php'),
                __DIR__.'/../config/simple-repository.php' => $this->app->basePath('config/simple-repository.php'),
            ], 'simple-repository');

            $this->commands([
                MakeRepository::class,
                MakeService::class,
            ]);
        }
    }
}
