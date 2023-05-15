<?php

namespace Modules\User\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Modules\Employee\Entities\Employee;
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
     * @return mixed
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): mixed
    {
        return $this->searchQuery($relations);
    }

    private function searchQuery($relations)
    {
        return $this->model
            ::query()
            ->where('id', auth()->user()->company_id)
            ->with($relations)
            ->first();
    }
}