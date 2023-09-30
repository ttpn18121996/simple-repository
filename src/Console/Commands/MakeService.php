<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service
        {service?}
        {--m|model=* : Dependency model class name}
        {--r|repo=* : Dependency repository class name}';

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
    protected $rootNamespaceModel = 'App\Models';

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
        $this->rootNamespaceModel = Config::get('simple-repository.root_namespace_model');

        $this->makeFolderService();

        $repositories = $this->option('repo') ?? [];
        $dependencyRepositories = [];

        foreach ($repositories as $repository) {
            $dependencyRepositories[] = $repository;
        }

        $models = $this->option('model') ?? [];
        $modelContract = [];

        foreach ($models as $model) {
            $modelContract[] = $this->rootNamespaceModel.'\\'.$model;
        }

        $servicePath = $this->laravel->basePath("app/Services/{$this->getServiceName()}.php");

        if (file_exists($servicePath)) {
            $this->error("{$this->getServiceName()}.php file is exists");

            return Command::FAILURE;
        }

        $serviceStubPath = $this->resolveStubPath('/stubs/service.stub');

        $this->createService($servicePath, $serviceStubPath);

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
    ): void {
        $file = fopen($path, 'w+');
        $serviceContent = file_get_contents($stubPath);

        [$useDependencyRepositories, $dependencyRepositories] = $this->getDependencyRepositories();
        [$useDependencyModels, $dependencyModels] = $this->getDependencyModels();

        $serviceContent = str_replace([
            '{{ namespace }}',
            '{{ use_dependencies }}',
            '{{ class }}',
            '{{ dependencies }}',
        ], [
            $this->rootNamespace,
            rtrim($useDependencyModels.$useDependencyRepositories, "\n"),
            $this->getServiceName(),
            rtrim($dependencyModels.$dependencyRepositories, "\n        "),
        ], $serviceContent);

        fwrite($file, $serviceContent);
        fclose($file);
    }

    protected function getDependencyRepositories(): array
    {
        $useDependencies = '';
        $dependencies = '';

        foreach (($this->option('repo') ?? []) as $repository) {
            $useDependencies .= "use {$this->rootNamespaceRepositoryContract}\\{$repository};\n";
            $dependencies .= "protected {$repository} $".Str::camel($repository).",\n        ";
        }

        return [
            ltrim($useDependencies),
            $dependencies,
        ];
    }

    protected function getDependencyModels(): array
    {
        $useDependencies = '';
        $dependencies = '';

        foreach (($this->option('model') ?? []) as $model) {
            $useDependencies .= "use {$this->rootNamespaceModel}\\{$model};\n";
            $dependencies .= "protected {$model} $".Str::camel($model).",\n        ";
        }

        return [
            ltrim($useDependencies),
            $dependencies,
        ];
    }
}
