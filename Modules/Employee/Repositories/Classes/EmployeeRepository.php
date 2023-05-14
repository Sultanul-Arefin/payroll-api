<?php

namespace Modules\Employee\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Repositories\Interfaces\EmployeeRepositoryInterface;


class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * Employee Repository constructor.
     *
     * @param Employee $model
     */
    public function __construct(Employee $model)
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