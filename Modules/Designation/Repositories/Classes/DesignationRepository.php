<?php

namespace Modules\Designation\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Designation\Entities\Designation;
use Modules\Designation\Repositories\Interfaces\DesignationInterface;

class DesignationRepository extends BaseRepository implements DesignationInterface
{
    /**
     * Designation Repository constructor.
     *
     * @param Designation $model
     */
    public function __construct(Designation $model)
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
            ->where('parent_id', null)
            ->with($relations)
            ->latest('id');
    }

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allDataWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator
    {
        return $this->searchAllQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchAllQuery($relations)
    {
        return $this->model
            ::query()
            ->where('company_id', auth()->user()->company_id)
            ->with($relations)
            ->latest('id');
    }
}