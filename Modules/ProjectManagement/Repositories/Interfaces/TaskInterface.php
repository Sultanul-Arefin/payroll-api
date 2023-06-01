<?php

namespace Modules\ProjectManagement\Repositories\Interfaces;


interface TaskInterface
{
    /**
     * @param int project_id
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return mixed
     */
    public function allWithSearch(
        int $project_id,
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
