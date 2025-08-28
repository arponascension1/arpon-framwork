<?php

namespace Arpon\Database\Pagination;

use Arpon\Http\Request;

class Paginator
{
    protected $items;
    protected $perPage;
    protected $currentPage;
    protected $path;

    public function __construct($items, $perPage, $currentPage = null, $options = [])
    {
        $this->items = $items;
        $this->perPage = $perPage;
        $this->path = $options['path'] ?? Request::capture()->path();
        $this->currentPage = $this->resolveCurrentPage($currentPage);
    }

    protected function resolveCurrentPage($currentPage)
    {
        if ($currentPage) {
            return $currentPage;
        }

        return (int) Request::capture()->input('page', 1);
    }

    public function items()
    {
        return $this->items;
    }

    public function currentPage()
    {
        return $this->currentPage;
    }

    public function perPage()
    {
        return $this->perPage;
    }

    public function path()
    {
        return $this->path;
    }
}