<?php

namespace SimpleRepository\Contracts;

interface Repository
{
    /**
     * Get a new Eloquent model instance.
     */
    public function model();

    /**
     * Set a list of model scopes to query.
     */
    public function useScope(array|string $scope);

    /**
     * Set a list of model relationships to query.
     */
    public function useWith(array|string $with);

    /**
     * Set a list of model has/doesn't have relationships to query.
     */
    public function useHas(array|string $has, bool $boolean = true);

    /**
     * Set a list of model doesn't have relationships to query.
     */
    public function useDoesntHave(array|string $doesntHave);
}
