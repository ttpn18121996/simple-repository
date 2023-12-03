<?php

namespace SimpleRepository;

use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use SimpleRepository\Contracts\Repository as RepositoryContract;
use SimpleRepository\Traits\HasFilter;
use SimpleRepository\Traits\Safetyable;

/**
 * @see \Illuminate\Database\Eloquent\Builder
 */
abstract class Repository implements RepositoryContract
{
    use HasFilter, Safetyable;

    /**
     * List of scopes to be attached when querying.
     *
     * @var array
     */
    protected array $scopes = [];

    /**
     * @var array
     */
    protected array $withs = [];

    /**
     * @var array
     */
    protected array $has = [];

    /**
     * @var array
     */
    protected array $doesntHave = [];

    /**
     * Get full name of model.
     *
     * @return string
     */
    abstract public function getModelName(): string;

    /**
     * Get a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model(array $attributes = [])
    {
        return Container::getInstance()->make($this->getModelName(), [
            'attributes' => $attributes,
        ]);
    }

    /**
     * Set a list of model scopes to query.
     *
     * @param  array|string  $scope
     * @return $this
     */
    public function useScope(array|string $scope): static
    {
        if (is_string($scope)) {
            $scope = [$scope];
        }

        $this->scopes = array_merge($this->scopes, $scope);

        return $this;
    }

    /**
     * Set a list of model relationships to query.
     *
     * @param  array|string  $with
     * @return $this
     */
    public function useWith(array|string $with): static
    {
        if (is_string($with)) {
            $with = [$with];
        }

        $this->withs = array_merge($this->withs, $with);

        return $this;
    }

    /**
     * Set a list of model has/doesn't have relationships to query.
     *
     * @param  array|string  $has
     * @param  bool  $boolean
     * @return $this
     */
    public function useHas(array|string $has, bool $boolean = true): static
    {
        if (is_string($has)) {
            $has = [$has];
        }

        if ($boolean) {
            $this->has = array_merge($this->has, $has);
        } else {
            $this->doesntHave = array_merge($this->doesntHave, $has);
        }

        return $this;
    }

    /**
     * Set a list of model doesn't have relationships to query.
     *
     * @param  array|string  $doesntHave
     * @return $this
     */
    public function useDoesntHave(array|string $doesntHave): static
    {
        return $this->useHas($doesntHave, false);
    }

    /**
     * Enable "filters" to use "useWith".
     *
     * @param  array  $relationValid
     * @param  array  $filters
     * @return $this
     */
    public function enableUseWith(array $relationValid, array $filters = []): static
    {
        if (Arr::has($filters, 'with') && ! array_diff($filters['with'], $relationValid)) {
            $this->useWith($filters['with']);
        }

        return $this;
    }

    /**
     * Get the list of the resource and handle filter.
     *
     * @param  array  $filters
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function getAll(array $filters = [], array $columns = ['*']): Collection
    {
        return $this->getBuilder($filters)->get($columns);
    }

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
    ): LengthAwarePaginator {
        $pageName = Arr::get($options, 'page_name', 'page');
        $page = Arr::get($filters, $pageName, 1);
        $perPage = Arr::get($filters, 'per_page', 10);

        return $this->getBuilder($filters)
            ->paginate($perPage, $columns, $pageName, $page)
            ->withQueryString();
    }

    /**
     * Get a specified resource with filter.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return mixed
     */
    public function getById($id, array $columns = ['*'])
    {
        return $this->buildRelationships()
            ->where($this->model()->getKeyName(), $id)
            ->first($columns);
    }

    /**
     * Get a builder that handles filters and relationships.
     *
     * @param  array  $filters
     * @return \Illuminate\Contracts\Database\Query\Builder
     */
    protected function getBuilder(array $filters = []): Builder
    {
        return $this->buildFilter($this->buildRelationships(), $filters);
    }

    /**
     * Build a query with relationships.
     *
     * @return \Illuminate\Contracts\Database\Query\Builder
     */
    protected function buildRelationships(): Builder
    {
        $query = $this->model()->query();

        if (! empty($this->withs)) {
            $query->with($this->withs);
        }

        if (! empty($this->scopes)) {
            foreach ($this->scopes as $key => $scope) {
                if (is_array($scope)) {
                    $query->{$key}(...$scope);
                } else {
                    $query->{$scope}();
                }
            }
        }

        if (! empty($this->has)) {
            foreach ($this->has as $relation => $callback) {
                if (is_numeric($relation)) {
                    $query->has($callback);
                    continue;
                }

                $query->whereHas($relation, $callback);
            }
        }

        if (! empty($this->doesntHave)) {
            foreach ($this->doesntHave as $relation => $callback) {
                if (is_numeric($relation)) {
                    $query->doesntHave($callback);

                    continue;
                }

                $query->whereDoesntHave($relation, $callback);
            }
        }

        return $query;
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->model()->{$method}(...$parameters);
    }
}
