<?php

declare(strict_types=1);

namespace Atom\Collections;

class PagedCollection extends ReadOnlyCollection
{
    private int $currentPage = 1;
    private int $pageSize = 0;
    private int $totalCount = 0;

    public static function fromPage(iterable $items, int $totalCount, int $currentPage, int $pageSize): static
    {
        if ($pageSize <= 0) {
            throw new \InvalidArgumentException("Page size must be greater than zero.");
        }

        $collection = new static($items);
        $collection->totalCount = $totalCount;
        $collection->pageSize = $pageSize;
        $collection->currentPage = max(1, $currentPage);

        return $collection;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages();
    }

    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = max(0, $totalCount);
    }

    public function getTotalCount(): int
    {
        return $this->totalCount();
    }

    public function setPageSize(int $pageSize): void
    {
        if ($pageSize <= 0) {
            throw new \InvalidArgumentException("Page size must be greater than zero.");
        }

        $this->pageSize = $pageSize;
    }

    public function getPageSize(): int
    {
        return $this->pageSize();
    }

    public function getTotalPages(): int
    {
        return $this->totalPages();
    }

    public function setTotalPages(int $totalPages): void
    {
        $this->totalCount = max(0, $totalPages) * max(1, $this->pageSize);
    }

    public function getCurrentPage(): int
    {
        return $this->page();
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = max(1, $currentPage);
    }

    public function page(): int
    {
        return $this->currentPage;
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    public function totalCount(): int
    {
        return $this->totalCount;
    }

    public function totalPages(): int
    {
        if ($this->pageSize <= 0) {
            return 1;
        }

        return max(1, (int) ceil($this->totalCount / $this->pageSize));
    }
}
