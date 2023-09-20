<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MakeRepository extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
        {repository?}
        {--m|model= : Dependency model class name}
        {--r|repo=Eloquents : The name of the directory containing the repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make repository file';

    /**
     * @var string
     */
    protected $rootNamespace = 'App\Repositories';

    /**
     * @var string
     */
    protected $rootNamespaceModel = 'App\Models';

    /**
     * @var string
     */
    protected $repositoryName;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->rootNamespace = Config::get('simple-repository.root_namespace_repository');
        $this->rootNamespaceModel = Config::get('simple-repository.root_namespace_model');

        $this->makeFolderRepositories();

        $model = $this->option('model') ?? '';
        $modelClass = $this->rootNamespaceModel.'\\'.$model;
        $repositoryPath = $this->laravel->basePath("app/Repositories/{$this->getRepo()}/{$this->getRepositoryName()}.php");

        if (file_exists($repositoryPath)) {
            $this->error("{$this->getRepositoryName()}.php file is exists");

            return Command::FAILURE;
        }

        $contractStubPath = $this->resolveStubPath('/stubs/repository.contract.stub');
        $repositoryStubPath = $this->resolveStubPath(class_exists($modelClass)
            ? '/stubs/repository.model.stub'
            : '/stubs/repository.stub');

        if (! is_dir($repositoryDir = $this->laravel->basePath("app/Repositories/{$this->getRepo()}"))) {
            mkdir($repositoryDir, 0777);
        }

        $this->createRepository($repositoryPath, $repositoryStubPath, $model, $modelClass);

        if (! is_dir($contractDir = $this->laravel->basePath('app/Repositories/Contracts'))) {
            mkdir($contractDir, 0777);
        }

        $this->createContract($contractStubPath);

        $this->updateServiceProvider();

        $this->info(sprintf(
            'Repository [%s] created successfully.',
            "app/Repositories/{$this->getRepo()}/{$this->getRepositoryName()}.php"
        ));

        return Command::SUCCESS;
    }

    /**
     * Make a new directory containing the repository if it does not already exist.
     */
    protected function makeFolderRepositories(): void
    {
        $dir = $this->laravel->basePath('app/Repositories');

        if (! is_dir($dir)) {
            mkdir($dir, 0777);
        }
    }

    /**
     * Create a new file Repository.
     */
    protected function createRepository(
        string $path,
        string $stubPath,
        string $model,
        string $modelClass,
    ): void {
        $file = fopen($path, 'w+');
        $repositoryContent = file_get_contents($stubPath);
        $repositoryContent = str_replace([
            '{{ namespace }}',
            '{{ model }}',
            '{{ class }}',
            '{{ model_basename }}',
        ], [
            $this->rootNamespace.'\\'.$this->getRepo(),
            $modelClass ?? '',
            $this->getRepositoryName(),
            $model ?? '',
        ], $repositoryContent);

        fwrite($file, $repositoryContent);
        fclose($file);
    }

    /**
     * Create a new file Contract.
     */
    protected function createContract(string $stubPath): void
    {
        $file = fopen($this->laravel->basePath("app/Repositories/Contracts/{$this->getRepositoryName()}.php"), 'w+');
        $contractContent = file_get_contents($stubPath);
        $contractContent = str_replace([
            '{{ class }}',
        ], [
            $this->getRepositoryName(),
        ], $contractContent);

        fwrite($file, $contractContent);
        fclose($file);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(string $modelClass): string
    {
        return $this->resolveStubPath(class_exists($modelClass)
            ? '/stubs/repository.model.stub'
            : '/stubs/repository.stub');
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

    /**
     * Get directory name of Repository.
     */
    protected function getRepo(): string
    {
        return $this->option('repo') ?? Config::get('simple-repository.default_repository', 'Eloquents');
    }

    /**
     * Get repository name.
     */
    protected function getRepositoryName(): string
    {
        if (! ($repository = $this->argument('repository'))) {
            if (! $this->repositoryName) {
                $this->repositoryName = $this->ask('What should the repository be named?');
            }

            return $this->repositoryName;
        }

        return $repository;
    }

    /**
     * Update the RepositoryServiceProvider content, declaring the bindings between abstract and concrete.
     */
    protected function updateServiceProvider(): void
    {
        $path = $this->laravel->basePath('app/Providers/SimpleRepositoryServiceProvider.php');
        $fileContent = file($path);
        $contentStart = 0;
        $repo = $this->getRepo();
        $name = $this->getRepositoryName();

        foreach ($fileContent as $line => $content) {
            if (str_contains($content, 'protected $repositories = [')) {
                $contentStart = $line;
            } elseif ($contentStart != 0 && str_contains($content, '];')) {
                $fileContent[$line] = <<<EOT
                        \App\Repositories\Contracts\\$name::class => \App\Repositories\\$repo\\$name::class,
                    ];

                EOT;

                break;
            }
        }

        file_put_contents($path, $fileContent);
    }
}
