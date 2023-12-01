<?php

namespace SimpleRepository\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface Repository
{
    /**
     * Get a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model(array $attributes = []);

    /**
     * Set a list of model scopes to query.
     *
     * @param  array|string  $scope
     * @return $this
     */
    public function useScope(array|string $scope): static;

    /**
     * Set a list of model relationships to query.
     *
     * @param  array|string  $with
     * @return $this
     */
    public function useWith(array|string $with): static;

    /**
     * Set a list of model has/doesn't have relationships to query.
     *
     * @param  array|string  $has
     * @param  bool  $boolean
     * @return $this
     */
    public function useHas(array|string $has, bool $boolean = true): static;

    /**
     * Set a list of model doesn't have relationships to query.
     *
     * @param  array|string  $doesntHave
     * @return $this
     */
    public function useDoesntHave(array|string $doesntHave): static;

    /**
     * Enable "filters" to use "useWith".
     *
     * @param  array  $relationValid
     * @param  array  $filters
     * @return $this
     */
    public function enableUseWith(array $relationValid, array $filters = []): static;

    /**
     * Get the list of the resource and handle filter.
     *
     * @param  array  $filters
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function getAll(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Get the list of the resource with pagination and handle filter.
     *
     * @param  array  $filters
     * @param  array  $columns
     * @param  array  $options
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPagination(
        array $filters = [],
        array $columns = ['*'],
        array $options = []
    ): LengthAwarePaginator;

    /**
     * Get a specified resource with filter.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return mixed
     */
    public function getById($id, array $columns = ['*']);
}
