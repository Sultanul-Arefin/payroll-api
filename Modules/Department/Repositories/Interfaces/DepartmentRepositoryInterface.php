<?php

namespace Modules\Department\Repositories\Interfaces;

interface DepartmentRepositoryInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;

    /**
     * @param  array|string[]  $columns
     */
    public function allDataWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
