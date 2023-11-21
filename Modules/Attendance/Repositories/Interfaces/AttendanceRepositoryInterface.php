<?php

namespace Modules\Attendance\Repositories\Interfaces;

interface AttendanceRepositoryInterface
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
