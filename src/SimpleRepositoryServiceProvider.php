<?php

namespace SimpleRepository;

use Illuminate\Support\ServiceProvider;
use SimpleRepository\Console\Commands\MakeRepositoryCommand;
use SimpleRepository\Console\Commands\MakeServiceCommand;

class SimpleRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
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
     *
     * @return void
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
                MakeRepositoryCommand::class,
                MakeServiceCommand::class,
            ]);
        }
    }
}
