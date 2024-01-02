<?php

namespace SimpleRepository\Traits;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;
use TypeError;

trait HasFilter
{
    /**
     * Build a query with field filters.
     *
     * @param  \Illuminate\Contracts\Database\Query\Builder  $query
     * @param  array  $filters
     * @return \Illuminate\Contracts\Database\Query\Builder
     */
    protected function buildFilter(Builder $query, array $filters = []): Builder
    {
        $search = Arr::get($filters, 'search');
        $orSearch = Arr::get($filters, 'or_search');
        $filter = Arr::get($filters, 'filter');
        $orFilter = Arr::get($filters, 'or_filter');
        $sort = Arr::get($filters, 'sort');

        $query->when(! empty($search), $this->whereSearch($search))
            ->when(! empty($orSearch), $this->whereSearch($orSearch, 'or'))
            ->when(! empty($filter), $this->whereFilter($filter))
            ->when(! empty($orFilter), $this->whereFilter($filter, 'or'));

        if (! empty($sort) && Arr::has($sort, ['field', 'direction'])) {
            $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
        }

        return $query;
    }

    /**
     * Resolve a closure for building a relative search query.
     *
     * @param  array|null  $search
     * @param  string  $boolean
     * @return \Closure
     */
    protected function whereSearch(?array $search, string $boolean = 'and'): Closure
    {
        return function ($query) use ($search, $boolean) {
            foreach ($search as $field => $value) {
                $field = $this->getTransferredField($field);

                $query->where($field, 'like', "%{$value}%", $boolean);
            }
        };
    }

    /**
     * Get the name of the transferred data field.
     *
     * @throws \TypeError
     */
    protected function getTransferredField(string $field): string
    {
        if (! property_exists(get_class($this), 'transferredFields')) {
            return $field;
        }

        if (! is_array($this->transferredFields)) {
            throw new TypeError(self::class.'::$transferredFields: Property $transferredFields must be of type array');
        }

        return Arr::get($this->transferredFields, $field, $field);
    }

    /**
     * Resolve a closure for building an absolute search query.
     *
     * @param  array|null  $filter
     * @param  string  $boolean
     * @return \Closure
     */
    protected function whereFilter(?array $filter, string $boolean = 'and'): Closure
    {
        return function ($query) use ($filter, $boolean) {
            foreach ($filter as $field => $value) {
                $field = $this->getTransferredField($field);

                $query->where($field, '=', $value, $boolean);
            }
        };
    }
}
