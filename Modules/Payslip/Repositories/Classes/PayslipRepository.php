<?php

namespace Modules\Payslip\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Payslip\Entities\Payslip;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipRepository extends BaseRepository implements PayslipRepositoryInterface
{
    /**
     * Payslip Repository constructor.
     */
    public function __construct(Payslip $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model::query()
                ->where('company_id', auth()->user()->company_id)
                ->when(
                    ! is_null(request('employee_id')),
                    fn (Builder $builder) => $builder->where(function ($query) {
                        $query
                            ->where('employee_id', request('employee_id'));
                    })
                )
                ->when(
                    ! is_null(request('search')),
                    fn (Builder $builder) => $builder->where(function ($query) {
                        $query
                            ->whereRelation(
                                'employee',
                                'name',
                                'LIKE',
                                '%'.request('search').'%'
                            )
                            ->orWhereRelation(
                                'employee',
                                'email',
                                'LIKE',
                                '%'.request('search').'%'
                            );
                    })
                )
                ->with($relations)
                ->latest();
    }

    /**
     * @param  array|string[]  $columns
     */
    public function getSalaryItemsCategory(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator {
        return $this->getSalaryItemsCategoryData($relations)->cursorPaginate($count, $columns);
    }

    public function getSalaryItemsCategoryData($relations)
    {
        return SalaryItemsCategory::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->whereHas(
                        'employeeSalaryItem', function (Builder $builder) {
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
