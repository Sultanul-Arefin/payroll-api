<?php

namespace Modules\Department\Repositories\Interfaces;


interface DepartmentRepositoryInterface
{
    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return mixed
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
