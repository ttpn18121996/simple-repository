<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

abstract class BaseCommand extends Command
{
    /**
     * Get the full name of the model.
     *
     * @param  string  $modelName
     * @return string
     */
    protected function getFullnameModel(string $modelName): string
    {
        return $this->namespaceModel()."\\{$modelName}";
    }

    /**
     * Get repository default directory name.
     *
     * @param  string|null  $repository
     * @return string
     */
    protected function getRepositoryDefault(?string $repository = null): string
    {
        return $repository ?? Config::get('simple-repository.default_repository');
    }

    /**
     * Get the namespace of the model.
     *
     * @return string
     */
    protected function namespaceModel(): string
    {
        $rootNamespace = $this->rootNamespace();

        return is_dir($this->laravel->basePath('app/Models'))
            ? $rootNamespace.'Models'
            : $rootNamespace;
    }

    /**
     * Get the namespace of the repository contract.
     *
     * @param  string|null  $repository
     * @return string
     */
    protected function namespaceRepository(?string $repository = null): string
    {
        return $this->rootNamespace().'Repositories\\'.$this->getRepositoryDefault($repository);
    }

    /**
     * Get the namespace of the repository contract.
     *
     * @return string
     */
    protected function namespaceRepositoryContract(): string
    {
        return $this->rootNamespace().'Repositories\\Contracts';
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../..'.$stub;
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }
}
