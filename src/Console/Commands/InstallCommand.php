<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use SimpleRepository\SimpleRepositoryServiceProvider;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simple-repository:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the SimpleRepository resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->callSilent('vendor:publish', [
            '--provider' => SimpleRepositoryServiceProvider::class,
        ]);

        $this->registerSimpleRepositoryServiceProvider();

        $this->components->info('SimpleRepository scaffolding was installed successfully.');
    }

    /**
     * Register the Fortify service provider in the application configuration file.
     */
    protected function registerSimpleRepositoryServiceProvider(): void
    {
        if (! method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            return;
        }

        ServiceProvider::addProviderToBootstrapFile(\App\Providers\SimpleRepositoryServiceProvider::class);
    }
}
