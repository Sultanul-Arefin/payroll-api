<?php

namespace Modules\User\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface UserRepositoryInterface
{
    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator;

    public function userDetailsUpdate($user_id,$attributes);
}