<?php

declare(strict_types=1);

namespace Atom\Collections;

class PagedCollection extends ReadOnlyCollection
{
    private int $currentPage = 0;
    private int $totalPages = 0;
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
        $collection->totalPages = (int) ceil($totalCount / $pageSize);

        return $collection;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function setTotalCount(int $totalCount): void
    {
        $this->totalCount = $totalCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function setTotalPages(int $totalPages): void
    {
        $this->totalPages = $totalPages;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }
}
