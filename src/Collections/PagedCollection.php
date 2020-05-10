<?php

namespace Atom\Collections;

class PagedCollection extends ReadOnlyCollection
{
    private $currentPage = 0;
    private $totalPages = 0;
    private $pageSize = 0;
    private $totalCount = 0;

    public function hasPrevious(): bool {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool {
        return $this->currentPage < $this->totalPages;
    }

    public function setTotalCount(int $totalCount): void {
        $this->totalCount = $totalCount;
    }
    
    public function getTotalCount(): int {
        return $this->totalCount;
    }

    public function setPageSize(int $pageSize): void {
        $this->pageSize = $pageSize;
    }

    public function getPageSize(): int {
        return $this->pageSize;
    }

    public function getTotalPages()
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

    public static function from(iterable $items, int $count, int $pageNumber, int $pageSize): self
	{
        $result = new PagedCollection($items);
        $result->setTotalCount($count);
        $result->setPageSize($pageSize);
        $result->setCurrentPage($pageNumber);
        $result->setTotalPages((int)ceil($count/$pageSize));
		return $result;
	}
}