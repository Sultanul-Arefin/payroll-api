<?php

namespace Modules\ProjectManagement\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Repositories\Interfaces\TaskInterface;

class TaskRepository extends BaseRepository implements TaskInterface
{
    /**
     * Task Repository constructor.
     *
     * @param Task $model
     */
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    /**
     * @param project_id
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        int $project_id,
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator
    {
        return $this->searchQuery($project_id, $relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($project_id, $relations)
    {
        return ProjectAssociatedColumn
            ::query()
            ->where('project_id', $project_id)
            ->with($relations)
            ->latest('id');
    }
}
