<?php

namespace SimpleRepository;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use SimpleRepository\Enums\SortDirection;

class FilterAdapter
{
    public function getArrayableData($data): array
    {
        if ($data instanceof Request) {
            return $data->query();
        }

        return collect($data)->toArray();
    }

    public static function makeDTO($data): FilterDTO
    {
        $instance = new static();
        $data = $instance->getArrayableData($data);

        return new FilterDTO(
            search: Arr::get($data, 'search'),
            orSearch: Arr::get($data, 'or_search'),
            filter: Arr::get($data, 'filter'),
            orFilter: Arr::get($data, 'or_filter'),
            sortField: Arr::get($data, 'sort.field'),
            sortDirection: SortDirection::tryFrom(Arr::get($data, 'sort.direction')) ?? SortDirection::ASC,
            deleted: Arr::get($data, 'deleted'),
        );
    }
}
