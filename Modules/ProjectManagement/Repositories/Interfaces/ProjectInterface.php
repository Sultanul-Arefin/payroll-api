<?php

namespace Modules\ProjectManagement\Repositories\Interfaces;


interface ProjectInterface
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
