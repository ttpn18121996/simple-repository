<?php

namespace SimpleRepository;

use SimpleRepository\Enums\SortDirection;

class FilterDTO
{
    public function __construct(
        protected ?array $search = null,
        protected ?array $orSearch = null,
        protected ?array $filter = null,
        protected ?array $orFilter = null,
        protected ?string $sortField = null,
        protected SortDirection $sortDirection = SortDirection::ASC,
        protected ?string $deleted = null,
    ) {}

    public function getSearch(): ?array
    {
        return $this->search;
    }

    public function setSearch(?array $search)
    {
        $this->search = $search;
    }

    public function getOrSearch(): ?array
    {
        return $this->orSearch;
    }

    public function setOrSearch(?array $orSearch)
    {
        $this->orSearch = $orSearch;
    }

    public function getFilter(): ?array
    {
        return $this->filter;
    }

    public function setFilter(?array $filter)
    {
        $this->filter = $filter;
    }

    public function getOrFilter(): ?array
    {
        return $this->orFilter;
    }

    public function setOrFilter(?array $orFilter)
    {
        $this->orFilter = $orFilter;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function setSortField(?string $field)
    {
        $this->sortField = $field;
    }

    public function getSortDirection(): SortDirection
    {
        return $this->sortDirection;
    }

    public function setSortDirection(SortDirection $direction)
    {
        return $this->sortDirection = $direction;
    }

    public function getDeleted(): ?string
    {
        return $this->deleted;
    }

    public function setDeleted(?string $deleted)
    {
        $this->deleted = $deleted;
    }

    public function hasSort(): bool
    {
        return isset($this->sortField) && isset($this->sortDirection);
    }
}
