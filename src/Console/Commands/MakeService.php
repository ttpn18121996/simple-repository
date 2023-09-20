<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service
        {service?}
        {--r|repo= : Dependency repository class name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make service file';

    /**
     * @var string
     */
    protected $rootNamespace = 'App\Services';

    /**
     * @var string
     */
    protected $rootNamespaceRepositoryContract = 'App\Repositories\Contracts';

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->rootNamespace = Config::get('simple-repository.root_namespace_service');
        $this->rootNamespaceRepositoryContract = Config::get('simple-repository.root_namespace_repository_contract');

        $this->makeFolderService();

        $repository = $this->option('repo') ?? '';
        $repositoryContract = $this->rootNamespaceRepositoryContract.'\\'.$repository;
        $servicePath = $this->laravel->basePath("app/Services/{$this->getServiceName()}.php");

        if (file_exists($servicePath)) {
            $this->error("{$this->getServiceName()}.php file is exists");

            return Command::FAILURE;
        }

        $serviceStubPath = $this->resolveStubPath(interface_exists($repositoryContract)
            ? '/stubs/service.repository.stub'
            : '/stubs/service.stub');

        $this->createService($servicePath, $serviceStubPath, $repository, $repositoryContract);

        $this->info(sprintf(
            'Service [%s] created successfully.',
            "app/Services/{$this->getServiceName()}.php"
        ));

        return Command::SUCCESS;
    }

    /**
     * Make a new directory containing the service if it does not already exist.
     */
    protected function makeFolderService(): void
    {
        $dir = $this->laravel->basePath('app/Services');

        if (! is_dir($dir)) {
            mkdir($dir, 0777);
        }
    }

    /**
     * Get repository name.
     */
    protected function getServiceName(): string
    {
        if (! ($service = $this->argument('service'))) {
            if (! $this->serviceName) {
                $this->serviceName = $this->ask('What should the service be named?');
            }

            return $this->serviceName;
        }

        return $service;
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../..'.$stub;
    }

    protected function createService(
        string $path,
        string $stubPath,
        string $repository,
        string $repositoryContract
    ): void {
        $file = fopen($path, 'w+');
        $serviceContent = file_get_contents($stubPath);
        $serviceContent = str_replace([
            '{{ namespace }}',
            '{{ repository_contract }}',
            '{{ class }}',
            '{{ repository_contract_basename }}',
        ], [
            $this->rootNamespace,
            $repositoryContract,
            $this->getServiceName(),
            $repository ?? '',
        ], $serviceContent);

        fwrite($file, $serviceContent);
        fclose($file);
    }
}
