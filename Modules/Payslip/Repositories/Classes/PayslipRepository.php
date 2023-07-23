<?php

namespace Modules\Payslip\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipRepository extends BaseRepository implements PayslipRepositoryInterface
{
    /**
     * Payslip Repository constructor.
     *
     * @param Payslip $model
     */
    public function __construct(Payslip $model)
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
            ->with($relations)
            ->latest();
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