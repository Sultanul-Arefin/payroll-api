<?php

namespace Modules\SalaryItemsName\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface SalaryItemsNameInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        Model $id,
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
