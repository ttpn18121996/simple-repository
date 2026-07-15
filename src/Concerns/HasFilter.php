<?php

namespace SimpleRepository\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use SimpleRepository\FilterAdapter;
use SimpleRepository\FilterDTO;

trait HasFilter
{
    protected array $transferredFields = [];

    /**
     * Build a query with field filters.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>  $query
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<*>
     */
    protected function buildFilter($query, array|FilterDTO $filters = [])
    {
        $filters = $filters instanceof FilterDTO ? $filters : FilterAdapter::makeDTO($filters);
        $search = $filters->getSearch();
        $orSearch = $filters->getOrSearch();
        $filter = $filters->getFilter();
        $orFilter = $filters->getOrFilter();
        $deleted = $filters->getDeleted();

        if ($deleted) {
            if ($query instanceof Builder) {
                $query->onlyTrashed();
            } else {
                $query->whereNotNull($deleted);
            }
        }

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

        if ($filters->hasSort()) {
            $query->orderBy($this->getTransferredField($filters->getSortField()), $filters->getSortDirection()->value);
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
     */
    protected function getTransferredField(string $field): string
    {
        return Arr::get($this->transferredFields, $field, $field);
    }
}
