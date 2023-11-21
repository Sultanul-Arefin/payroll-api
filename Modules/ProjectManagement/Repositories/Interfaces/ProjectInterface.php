<?php

namespace Modules\ProjectManagement\Repositories\Interfaces;

interface ProjectInterface
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
