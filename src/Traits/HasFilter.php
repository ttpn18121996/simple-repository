<?php

namespace SimpleRepository\Traits;

use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;

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
     * @param  array  $search
     * @param  string  $boolean
     * @return \Closure
     */
    protected function whereSearch(array $search, string $boolean = 'and'): Closure
    {
        return function ($query) use ($search, $boolean) {
            foreach ($search as $field => $value) {
                $query->where($field, 'like', "%{$value}%", $boolean);
            }
        };
    }

    /**
     * Resolve a closure for building an absolute search query.
     *
     * @param  array  $filter
     * @param  string  $boolean
     * @return \Closure
     */
    protected function whereFilter(array $filter, string $boolean = 'and'): Closure
    {
        return function ($query) use ($filter, $boolean) {
            foreach ($filter as $field => $value) {
                $query->where($field, '=', $value, $boolean);
            }
        };
    }
}
