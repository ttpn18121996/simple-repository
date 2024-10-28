<?php

namespace SimpleRepository\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use SimpleRepository\RepositoryMakeModelException;

trait HasEloquentSupport
{
    /**
     * List of scopes to be attached when querying.
     */
    protected array $scopes = [];

    protected array $withs = [];

    protected array $has = [];

    protected array $doesntHave = [];

    /**
     * Get a new Eloquent model instance.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model(array $attributes = [])
    {
        if (! property_exists($this, 'modelName') || is_null($this->modelName)) {
            throw new RepositoryMakeModelException('Model name not set yet. You must set the modelName property.');
        }

        $modelName = $this->modelName;

        $model = Container::getInstance()->make($modelName, [
            'attributes' => $attributes,
        ]);

        if (! $model instanceof Model) {
            throw new RepositoryMakeModelException('Class '.$modelName.' must be an instance of '.Model::class.
            '. The model name provided in the modelName property is not the name of a class instance of a '.
            Model::class.' class.');
        }

        return $model;
    }

    /**
     * Get the list of the resource with pagination and handle filter.
     */
    public function getPagination(
        array $filters = [],
        array $columns = ['*'],
        array $options = [],
    ): LengthAwarePaginator {
        $pageName = Arr::get($options, 'page_name', 'page');
        $page = Arr::get($filters, $pageName, 1);
        $perPage = Arr::get($filters, 'per_page', 10);

        $query = $this->buildRelationships();

        if (method_exists($this, 'buildFilter')) {
            $query = $this->buildFilter($query, $filters);
        }

        if (method_exists($this, 'getBuild')) {
            $query = $this->getBuilder($query, $filters);
        }

        return $query->paginate($perPage, $columns, $pageName, $page)->withQueryString();
    }

    /**
     * Set a list of model scopes to query.
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
     */
    public function useDoesntHave(array|string $doesntHave): static
    {
        return $this->useHas($doesntHave, false);
    }

    /**
     * Enable "filters" to use "useWith".
     */
    public function enableUseWith(array $relationValid, array $filters = []): static
    {
        if (Arr::has($filters, 'with') && ! array_diff($filters['with'], $relationValid)) {
            $this->useWith($filters['with']);
        }

        return $this;
    }

    /**
     * Build a query with relationships.
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
     * @throws \InvalidArgumentException
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->model()->{$method}(...$parameters);
    }
}
