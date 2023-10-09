<?php

namespace SimpleRepository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface Repository
{
    /**
     * Get a new Eloquent model instance.
     */
    public function model(array $attributes = []);

    /**
     * Set a list of model scopes to query.
     */
    public function useScope(array|string $scope): static;

    /**
     * Set a list of model relationships to query.
     */
    public function useWith(array|string $with): static;

    /**
     * Set a list of model has/doesn't have relationships to query.
     */
    public function useHas(array|string $has, bool $boolean = true): static;

    /**
     * Set a list of model doesn't have relationships to query.
     */
    public function useDoesntHave(array|string $doesntHave): static;

    /**
     * Enable "filters" to use "useWith".
     */
    public function enableUseWith(array $relationValid, array $filters = []): static;

    /**
     * Get the list of the resource and handle filter.
     */
    public function getAll(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Get the list of the resource with pagination and handle filter.
     */
    public function getPagination(
        array $filters = [],
        array $columns = ['*'],
        array $options = []
    ): LengthAwarePaginator;

    /**
     * Get a specified resource with filter.
     */
    public function getById($id, array $columns = ['*']);
}
