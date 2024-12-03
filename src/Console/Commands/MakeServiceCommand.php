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
     */
    protected ?string $serviceName = null;

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
            "app/Services/{$this->getServiceName()}.php",
        ));

        return BaseCommand::SUCCESS;
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
     */
    protected function createService(
        string $path,
        string $stubPath,
    ): void {
        $file = fopen($path, 'w+');
        $serviceContent = file_get_contents($stubPath);

        if ($this->option('model')) {
            $serviceContent = $this->setProperties($serviceContent);
        } elseif ($this->option('repo')) {
            $serviceContent = $this->setDependencies($serviceContent);
        }

        fwrite($file, $serviceContent);
        fclose($file);
    }

    /**
     * Set the content with dependencies for service file.
     */
    protected function setDependencies(string $serviceContent): string
    {
        [$namespaces, $dependencies] = $this->getDependencyRepositories();

        if (! empty($dependencies)) {
            $dependencies = "    public function __construct(\n        "
                .rtrim($dependencies, "\n        ")
                ."\n    ) {}";
        }

        return str_replace([
            '{{ namespace }}',
            '{{ namespacedDependencies }}',
            '{{ class }}',
            '{{ properties }}',
            '{{ dependencies }}',
        ], [
            $this->rootNamespace().'Services',
            rtrim($namespaces, "\n"),
            $this->getServiceName(),
            '',
            rtrim($dependencies, "\n        "),
        ], $serviceContent);
    }

    /**
     * Set the content with properties for service file.
     */
    protected function setProperties(string $serviceContent): string
    {
        [$namespaces, $properties] = $this->getPropertyModel();
        $properties = rtrim($properties, "\n");

        return str_replace([
            '{{ namespace }}',
            '{{ namespacedDependencies }}',
            '{{ class }}',
            '{{ properties }}',
            '{{ dependencies }}',
        ], [
            $this->rootNamespace().'Services',
            rtrim($namespaces, "\n"),
            $this->getServiceName(),
            $properties,
            '',
        ], $serviceContent);
    }

    /**
     * Get the list of dependent repositories.
     */
    protected function getDependencyRepositories(): array
    {
        $namespacedRepositories = '';
        $dependencies = '';
        $repositories = $this->option('repo') ?? [];
        $repositories = is_string($repositories) ? [$repositories] : $repositories;
        sort($repositories);

        foreach ($repositories as $repository) {
            $propertyName = Str::beforeLast(Str::camel($repository), 'Repository');
            $namespacedRepositories .= "\nuse {$this->namespaceRepositoryContract()}\\{$repository};\n";
            $dependencies .= "protected {$repository} \${$propertyName},\n        ";
        }

        return [
            $namespacedRepositories,
            $dependencies,
        ];
    }

    /**
     * Get the list of dependent models.
     */
    protected function getPropertyModel(): array
    {
        $namespacedModels = '';
        $properties = '';

        $models = $this->option('model') ?? [];
        $models = is_string($models) ? [$models] : $models;
        sort($models);

        foreach ($models as $index => $model) {
            $namespacedModels .= "\nuse {$this->getFullnameModel($model)};";

            if ($index == count($models) - 1) {
                $namespacedModels .= "\nuse SimpleRepository\Attributes\ModelFactory;";
            }

            $properties .= "    #[ModelFactory({$model}::class)]\n";
            $properties .= "    protected ?{$model} $".Str::camel($model)." = null;\n\n";
        }

        return [
            $namespacedModels,
            $properties,
        ];
    }
}
