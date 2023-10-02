<?php

namespace SimpleRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

abstract class BaseCommand extends Command
{
    /**
     * Get the full name of the model.
     */
    protected function getFullnameModel(string $modelName): string
    {
        return $this->namespaceModel()."\\{$modelName}";
    }

    /**
     * Get repository default directory name.
     */
    protected function getRepositoryDefault(?string $repository = null): string
    {
        return $repository ?? Config::get('simple-repository.default_repository');
    }

    /**
     * Get the namespace of the model.
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
     */
    protected function namespaceRepository(?string $repository = null): string
    {
        return $this->rootNamespace().'Repositories\\'.$this->getRepositoryDefault($repository);
    }

    /**
     * Get the namespace of the repository contract.
     */
    protected function namespaceRepositoryContract(): string
    {
        return $this->rootNamespace().'Repositories\\Contracts';
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.'/../../..'.$stub;
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return $this->laravel->getNamespace();
    }
}
