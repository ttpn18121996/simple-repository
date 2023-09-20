<?php

namespace SimpleRepository;

abstract class Service
{
    /**
     * Get full name of repository contract.
     */
    abstract public function getRepositoryName();

    /**
     * Get a new repository instance.
     */
    public function repository()
    {
        return app($this->getRepositoryName());
    }
}
