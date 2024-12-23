<?php

namespace Modules\SalaryItemsName\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\SalaryItemsName\Entities\SalaryItemsName;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

class SalaryItemsNameRepository extends BaseRepository implements SalaryItemsNameInterface
{
    /**
     * SalaryItemsName Repository constructor.
     */
    public function __construct(SalaryItemsName $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        Model $id,
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator {
        return $this->searchQuery($relations, $id)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations, $resource)
    {
        return $this->model::query()
                ->when(
                    !is_null(request('tax_type')) && request('tax_type') == "straight",
                    fn(Builder $builder) => $builder->where(function($query){
                        $query->where('is_threshold', SalaryItemsName::INCOME_TAX_STRAIGHT);
                    })
                )
                ->when(
                    !is_null(request('tax_type')) && request('tax_type') == "threshold",
                    fn(Builder $builder) => $builder->where(function($query){
                        $query->where('is_threshold', SalaryItemsName::INCOME_TAX_THRESHOLD);
                    })
                )
                ->when(
                    !is_null(request('country_id')),
                    fn(Builder $builder) => $builder->where(function($query){
                        $query->where('country_id', request('country_id'));
                    })
                )
                ->when(
                    is_null(request('country_id')),
                    fn(Builder $builder) => $builder->where(function($query){
                        $query->whereNull('country_id');
                    })
                )
                ->where('salary_items_category_id', $resource->id)
                ->where('company_id', auth()->user()->company_id)
                ->with($relations)
                ->latest();
    }
}
