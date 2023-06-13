<?php

namespace Modules\SalaryItemsName\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\SalaryItemsName\Entities\SalaryItemsName;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

class SalaryItemsNameRepository extends BaseRepository implements SalaryItemsNameInterface
{
    /**
     * SalaryItemsName Repository constructor.
     *
     * @param SalaryItemsName $model
     */
    public function __construct(SalaryItemsName $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Model $id
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        Model $id,
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator
    {
        return $this->searchQuery($relations, $id)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations, $resource)
    {
        return $this->model
            ::query()
            ->where('salary_items_category_id', $resource->id)
            ->where('company_id', auth()->user()->company_id)
            ->with($relations)
            ->latest();
    }
}
