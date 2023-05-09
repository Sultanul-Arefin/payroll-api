<?php

namespace Modules\Package\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface PackageRepositoryInterface
{
    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator;
}
