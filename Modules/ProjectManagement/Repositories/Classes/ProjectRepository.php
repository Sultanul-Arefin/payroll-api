<?php

namespace Modules\ProjectManagement\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectInterface;

class ProjectRepository extends BaseRepository implements ProjectInterface
{
    /**
     * Project Repository constructor.
     */
    public function __construct(Project $model)
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
                ->with($relations)
                ->latest('id');
    }
}
