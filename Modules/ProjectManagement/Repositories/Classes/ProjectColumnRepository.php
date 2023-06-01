<?php

namespace Modules\ProjectManagement\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectColumn;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectColumnInterface;

class ProjectColumnRepository extends BaseRepository implements ProjectColumnInterface
{
    /**
     * ProjectColumn Repository constructor.
     *
     * @param ProjectColumn $model
     */
    public function __construct(ProjectColumn $model)
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
            ->latest('id');
    }
}
