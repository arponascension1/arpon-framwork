<?php

namespace Arpon\Database\Pagination;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class LengthAwarePaginator extends Paginator implements Countable, IteratorAggregate
{
    protected $total;
    protected $lastPage;

    public function __construct($items, $total, $perPage, $currentPage = null, $options = [])
    {
        parent::__construct($items, $perPage, $currentPage, $options);
        $this->total = $total;
        $this->lastPage = (int) ceil($total / $perPage);
    }

    public function total()
    {
        return $this->total;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage() < $this->lastPage();
    }

    public function nextPageUrl(): ?string
    {
        if ($this->hasMorePages()) {
            return $this->url($this->currentPage() + 1);
        }

        return null;
    }

    public function previousPageUrl(): ?string
    {
        if ($this->currentPage() > 1) {
            return $this->url($this->currentPage() - 1);
        }

        return null;
    }

    public function url($page): string
    {
        if ($page <= 0) {
            $page = 1;
        }

        $query = parse_url($this->path, PHP_URL_QUERY) ? '&' : '?';

        return $this->path . $query . 'page=' . $page;
    }

    public function links($view = null): string
    {
        if ($this->lastPage() <= 1) {
            return '';
        }

        $html = '<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">';
        $html .= '<div class="flex-1 flex items-center justify-between">';
        $html .= '<div>';

        // Previous Page Link
        if ($this->currentPage() > 1) {
            $html .= sprintf(
                '<a href="%s" rel="prev" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">&laquo; Previous</a>',
                $this->previousPageUrl()
            );
        } else {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">&laquo; Previous</span>';
        }

        $html .= '</div>';
        $html .= '<div>';
        $html .= '<nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';

        // Pagination Elements
        for ($i = 1; $i <= $this->lastPage(); $i++) {
            if ($i == $this->currentPage()) {
                $html .= sprintf(
                    '<span aria-current="page" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-blue-500 border border-blue-500 cursor-default leading-5">%d</span>',
                    $i
                );
            } else {
                $html .= sprintf(
                    '<a href="%s" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">%d</a>',
                    $this->url($i),
                    $i
                );
            }
        }

        $html .= '</nav>';
        $html .= '</div>';
        $html .= '<div>';

        // Next Page Link
        if ($this->hasMorePages()) {
            $html .= sprintf(
                '<a href="%s" rel="next" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">Next &raquo;</a>',
                $this->nextPageUrl()
            );
        } else {
            $html .= '<span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">Next &raquo;</span>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</nav>';

        return $html;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items->all());
    }
}