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
    protected $description = 'Create a new repository class';

    /**
     * Name of repository.
     */
    protected ?string $repositoryName = null;

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

        $contractStubPath = $this->resolveStubPath(class_exists($modelClass)
            ? '/stubs/repository.contract.model.stub'
            : '/stubs/repository.contract.stub',
        );
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

        if (! $this->updateOldServiceProvider()) {
            $this->updateServiceProvider();
        }

        $this->info(sprintf(
            'Repository [%s] created successfully.',
            $this->getRepositoryPath($this->getRepositoryName()),
        ));

        return BaseCommand::SUCCESS;
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
            '{{ namespacedModel }}',
            '{{ class }}',
            '{{ model }}',
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
     * Get directory path of Repository.
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
     * Update the SimpleRepositoryServiceProvider content, declaring the bindings between abstract and concrete.
     */
    protected function updateServiceProvider(string $propertyName = 'public $bindings'): bool
    {
        $path = $this->laravel->basePath('app/Providers/SimpleRepositoryServiceProvider.php');
        $fileContent = file($path);
        $contentStart = 0;
        $name = $this->getRepositoryName();

        foreach ($fileContent as $line => $content) {
            $repositoryContract = $this->namespaceRepositoryContract()."\\$name::class";
            $repositoryClass = $this->namespaceRepository($this->option('repo'))."\\$name::class";

            if (str_contains($content, $propertyName.' = [')) {
                $contentStart = $line;
            } elseif (str_contains($content, $repositoryContract.' => ')) {
                $fileContent[$line] = preg_replace(
                    '/('.preg_quote($repositoryContract).' \=\> )(.*?)$/',
                    '$1\\'.$repositoryClass.',',
                    $content,
                );

                break;
            } elseif ($contentStart != 0 && str_contains($content, '];')) {
                $fileContent[$line] = <<<EOT
                        \\$repositoryContract => \\$repositoryClass,
                    ];

                EOT;

                break;
            }
        }

        if ($contentStart === 0) {
            return false;
        }

        file_put_contents($path, $fileContent);

        return true;
    }

    /**
     * This method is used to update the value of the bindings attribute in the old version.
     */
    protected function updateOldServiceProvider(): bool
    {
        return $this->updateServiceProvider('protected $repositories');
    }
}
