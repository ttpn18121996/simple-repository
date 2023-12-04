<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Support\Str;

class MakeServiceCommand extends BaseCommand
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
    protected $description = 'Create a new service class';

    /**
     * Name of service.
     *
     * @var string
     */
    protected $serviceName;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->makeFolderService();

        $servicePath = $this->laravel->basePath("app/Services/{$this->getServiceName()}.php");

        if (file_exists($servicePath)) {
            $this->error("{$this->getServiceName()}.php file is exists");

            return BaseCommand::FAILURE;
        }

        $serviceStubPath = $this->resolveStubPath('/stubs/service.stub');

        $this->createService($servicePath, $serviceStubPath);

        $this->info(sprintf(
            'Service [%s] created successfully.',
            "app/Services/{$this->getServiceName()}.php"
        ));

        return BaseCommand::SUCCESS;
    }

    /**
     * Make a new directory containing the service if it does not already exist.
     *
     * @return void
     */
    protected function makeFolderService(): void
    {
        $dir = $this->laravel->basePath('app/Services');

        if (! is_dir($dir)) {
            mkdir($dir, 0777);
        }

        $this->makeBaseService();
    }

    /**
     * Create a new service file for BaseService class.
     */
    protected function makeBaseService(): void
    {
        $stubPath = $this->resolveStubPath('/stubs/service.base.stub');
        $filePath = $this->laravel->basePath('app/Services/Service.php');

        if (file_exists($filePath)) {
            return;
        }

        $file = fopen($filePath, 'w+');
        $content = file_get_contents($stubPath);

        fwrite($file, $content);
        fclose($file);
    }

    /**
     * Get repository name.
     *
     * @return string
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
     * Create a new file Service.
     *
     * @param  string  $path
     * @param  string  $stubPath
     * @return void
     */
    protected function createService(
        string $path,
        string $stubPath,
    ): void {
        $file = fopen($path, 'w+');
        $serviceContent = file_get_contents($stubPath);

        [$namespacedRepositories, $dependencyRepositories] = $this->getDependencyRepositories();
        [$namespacedModels, $dependencyModels] = $this->getDependencyModels();

        $serviceContent = str_replace([
            '{{ namespace }}',
            '{{ namespacedDependencies }}',
            '{{ class }}',
            '{{ dependencies }}',
        ], [
            $this->rootNamespace().'Services',
            rtrim($namespacedModels.$namespacedRepositories, "\n"),
            $this->getServiceName(),
            rtrim($dependencyModels.$dependencyRepositories, "\n        "),
        ], $serviceContent);

        fwrite($file, $serviceContent);
        fclose($file);
    }

    /**
     * Get the list of dependent repositories.
     *
     * @return array
     */
    protected function getDependencyRepositories(): array
    {
        $namespacedRepositories = '';
        $dependencies = '';

        foreach (($this->option('repo') ?? []) as $repository) {
            $namespacedRepositories .= "use {$this->namespaceRepositoryContract()}\\{$repository};\n";
            $dependencies .= "protected {$repository} $".Str::camel($repository).",\n        ";
        }

        return [
            ltrim($namespacedRepositories),
            $dependencies,
        ];
    }

    /**
     * Get the list of dependent models.
     *
     * @return array
     */
    protected function getDependencyModels(): array
    {
        $namespacedModels = '';
        $dependencies = '';

        foreach (($this->option('model') ?? []) as $model) {
            $namespacedModels .= "use {$this->getFullnameModel($model)};\n";
            $dependencies .= "protected {$model} $".Str::camel($model).",\n        ";
        }

        return [
            ltrim($namespacedModels),
            $dependencies,
        ];
    }
}
