<?php

namespace SimpleRepository\Console\Commands;

class MakeRepositoryCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository
        {repository?}
        {--m|model= : Dependency model class name}
        {--r|repo= : The name of the directory containing the repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make repository file';

    /**
     * Name of repository.
     *
     * @var string
     */
    protected $repositoryName;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->makeFolderRepositories();

        $model = $this->option('model') ?? '';
        $modelClass = $this->getFullnameModel($model);
        $repositoryPath = $this->getRepositoryPath($this->getRepositoryName());

        if (file_exists($repositoryPath)) {
            $this->error("{$this->getRepositoryName()}.php file is exists");

            return BaseCommand::FAILURE;
        }

        $contractStubPath = $this->resolveStubPath('/stubs/repository.contract.stub');
        $repositoryStubPath = $this->resolveStubPath(class_exists($modelClass)
            ? '/stubs/repository.model.stub'
            : '/stubs/repository.stub');

        if (! is_dir($repositoryDir = $this->getRepositoryPath())) {
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
            $this->getRepositoryPath($this->getRepositoryName())
        ));

        return BaseCommand::SUCCESS;
    }

    /**
     * Make a new directory containing the repository if it does not already exist.
     *
     * @return void
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
     *
     * @param  string  $path
     * @param  string  $stubPath
     * @param  string  $model
     * @param  string  $modelClass
     * @return void
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
            $this->namespaceRepository($this->getRepositoryDefault($this->option('repo'))),
            $modelClass ?? '',
            $this->getRepositoryName(),
            $model ?? '',
        ], $repositoryContent);

        fwrite($file, $repositoryContent);
        fclose($file);
    }

    /**
     * Create a new file Contract.
     *
     * @param  string  $stubPath
     * @return void
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
     *
     * @param  string  $modelClass
     * @return string
     */
    protected function getStub(string $modelClass): string
    {
        return $this->resolveStubPath(class_exists($modelClass)
            ? '/stubs/repository.model.stub'
            : '/stubs/repository.stub');
    }

    /**
     * Get directory path of Repository.
     *
     * @param  string|null  $repositoryName
     * @return string
     */
    protected function getRepositoryPath(?string $repositoryName = null): string
    {
        $repository = $this->getRepositoryDefault($this->option('repo'));
        $repositoryDir = $this->laravel->basePath('app/Repositories/'.$repository);

        return empty($repositoryName)
            ? $repositoryDir
            : "{$repositoryDir}/{$repositoryName}.php";
    }

    /**
     * Get repository name.
     *
     * @return string
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
     *
     * @return void
     */
    protected function updateServiceProvider(): void
    {
        $path = $this->laravel->basePath('app/Providers/SimpleRepositoryServiceProvider.php');
        $fileContent = file($path);
        $contentStart = 0;
        $name = $this->getRepositoryName();

        foreach ($fileContent as $line => $content) {
            if (str_contains($content, 'protected $repositories = [')) {
                $contentStart = $line;
            } elseif ($contentStart != 0 && str_contains($content, '];')) {
                $repositoryContract = $this->namespaceRepositoryContract()."\\$name::class";
                $repositoryClass = $this->namespaceRepository($this->option('repo'))."\\$name::class";
                $fileContent[$line] = <<<EOT
                        \\$repositoryContract => \\$repositoryClass,
                    ];

                EOT;

                break;
            }
        }

        file_put_contents($path, $fileContent);
    }
}
