<?php

namespace Modules\Payslip\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipRepository extends BaseRepository implements PayslipRepositoryInterface
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

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    function getSalaryItemsCategory(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator {
        return $this->getSalaryItemsCategoryData($relations)->cursorPaginate($count, $columns);
    }

    function getSalaryItemsCategoryData($relations) {
        return SalaryItemsCategory::query()
            ->whereHas(
                'salaryItemsName', function(Builder $builder){
                    $builder->whereHas(
                        'employeeSalaryItem', function(Builder $builder){
                            $builder
                                ->where('company_id', auth()->user()->company_id)
                                ->where('employee_id', request('employee_id'));
                        }
                    );
                }
            )
            ->with($relations)
            ->latest();
    }
}