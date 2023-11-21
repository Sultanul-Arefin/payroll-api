<?php

namespace Modules\SalaryItemsCategory\Repositories\Interfaces;

interface SalaryItemsCategoryInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
