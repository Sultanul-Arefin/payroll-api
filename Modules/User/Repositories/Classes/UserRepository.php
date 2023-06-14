<?php

namespace Modules\User\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Employee\Entities\Employee;
use Modules\User\Entities\UserDetails;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * User Repository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator
    {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model
            ::query()
            ->where('company_id', auth()->user()->company_id)
            ->when(
                !is_null(request('department_id')),
                fn(Builder $builder) => $builder->where(function ($query) {
                    $query
                        ->where(
                            'department_id',
                            request('department_id')
                        );
                })
            )
            ->with($relations)
            ->latest();
    }

    public function userDetailsUpdate($user_id,$attributes){
        return UserDetails::where('user_id',$user_id)?->update($attributes);
    }
}