<?php

namespace Modules\Designation\Repositories\Interfaces;

interface DesignationInterface
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
