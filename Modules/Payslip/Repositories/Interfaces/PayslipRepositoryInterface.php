<?php

namespace Modules\Payslip\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface PayslipRepositoryInterface
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

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    function getSalaryItemsCategory(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator;
}