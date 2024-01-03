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
     */
    protected function buildFilter(Builder $query, array $filters = []): Builder
    {
        $search = Arr::get($filters, 'search');
        $orSearch = Arr::get($filters, 'or_search');
        $filter = Arr::get($filters, 'filter');
        $orFilter = Arr::get($filters, 'or_filter');
        $sort = Arr::get($filters, 'sort');

        if (! empty($search)) {
            $query->where($this->whereSearch($search));
        }
        
        if (! empty($orSearch)) {
            $query->where($this->whereSearch($orSearch, 'or'));
        }

        if (! empty($filter)) {
            $query->where($this->whereFilter($filter));
        }
        
        if (! empty($orFilter)) {
            $query->where($this->whereFilter($orFilter, 'or'));
        }

        if (! empty($sort) && Arr::has($sort, ['field', 'direction'])) {
            $query->orderBy($this->getTransferredField($sort['field']), $sort['direction'] ?? 'asc');
        }

        return $query;
    }

    /**
     * Resolve a closure for building a relative search query.
     */
    protected function whereSearch(?array $search, string $boolean = 'and'): Closure
    {
        return function ($query) use ($search, $boolean) {
            foreach ($search as $field => $value) {
                $query->where($this->getTransferredField($field), 'like', "%{$value}%", $boolean);
            }
        };
    }

    /**
     * Resolve a closure for building an absolute search query.
     */
    protected function whereFilter(?array $filter, string $boolean = 'and'): Closure
    {
        return function ($query) use ($filter, $boolean) {
            foreach ($filter as $field => $value) {
                $query->where($this->getTransferredField($field), '=', $value, $boolean);
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
}
