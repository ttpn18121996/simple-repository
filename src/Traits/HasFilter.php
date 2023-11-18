<?php

namespace SimpleRepository\Traits;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Arr;

trait HasFilter
{
    /**
     * Build a query with field filters.
     */
    protected function buildFilter(Builder $query, array $filters = []): Builder
    {
        $search = Arr::get($filters, 'search');
        $filter = Arr::get($filters, 'filter');
        $sort = Arr::get($filters, 'sort');

        if (! empty($search)) {
            $query->where(function ($query) use ($search) {
                foreach ($search as $field => $value) {
                    $query->where($field, 'like', "%{$value}%");
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

        if (! empty($sort) && Arr::has($sort, ['field', 'direction'])) {
            $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
        }

        return $query;
    }
}
