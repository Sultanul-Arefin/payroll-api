<?php

namespace Modules\SalaryItemsName\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface SalaryItemsNameInterface
{
    /**
     * @param Model $id
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return mixed
     */
    public function allWithSearch(
        Model $id,
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
