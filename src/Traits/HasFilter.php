<?php

namespace SimpleRepository\Traits;

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

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                foreach ($search as $field => $value) {
                    $query->where($field, 'like', "%{$value}%");
                }
            });
        }

        if (! empty($orSearch)) {
            $query->where(function ($query) use ($orSearch) {
                foreach ($orSearch as $field => $value) {
                    $query->orWhere($field, 'like', "%{$value}%");
                }
            });
        }

        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                foreach ($filter as $field => $value) {
                    $query->where($field, $value);
                }
            });
        }
        
        if (! empty($orFilter)) {
            $query->where(function ($query) use ($orFilter) {
                foreach ($orFilter as $field => $value) {
                    $query->orWhere($field, $value);
                }
            });
        }

        if (! empty($sort) && Arr::has($sort, ['field', 'direction'])) {
            $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
        }

        return $query;
    }
}
