<?php

namespace Modules\User\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface UserRepositoryInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator;

    public function userDetailsUpdate($user_id, $attributes);

    /**
     * user status active or inactive
     */
    public function userStatus(object $user, int $statusTypes): bool;

    public function userDocument($request, $heading_type, $item_type, $user_id);
}
