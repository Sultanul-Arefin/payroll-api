<?php

namespace Modules\SalaryItemsCategory\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;
use Modules\SalaryItemsCategory\Repositories\Interfaces\SalaryItemsCategoryInterface;

class SalaryItemsCategoryRepository extends BaseRepository implements SalaryItemsCategoryInterface
{
    /**
     * SalaryItemsCategory Repository constructor.
     */
    public function __construct(SalaryItemsCategory $model)
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
                ->when(
                    ! is_null(request('add_new_salary_item')) || ! is_null(request('add_new_payslip_item')),
                    fn (Builder $builder) => $builder->whereNotIn(
                        'id',
                        [1, 2]
                    )
                )
                ->when(
                    ! is_null(request('country_wise_salary_items')),
                    fn (Builder $builder) => $builder->whereNotIn(
                        'id',
                        [1, 2, 5]
                    )
                )
                ->with($relations)
                ->latest();
    }
}
