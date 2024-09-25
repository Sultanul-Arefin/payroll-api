<?php

namespace Modules\ProjectManagement\Repositories\Classes;
use App\Http\Traits\Attachment;
use App\Exceptions\CustomException;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskFile;
use Modules\ProjectManagement\Http\Traits\TasksTrait;
use Modules\ProjectManagement\Repositories\Interfaces\TaskInterface;

class TaskRepository extends BaseRepository implements TaskInterface
{
    use Attachment;
    /**
     * Task Repository constructor.
     */
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    /**
     * @param project_id
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        int $project_id,
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): CursorPaginator {
        return $this->searchQuery($project_id, $relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($project_id, $relations)
    {
        return ProjectAssociatedColumn::query()
                ->where('project_id', $project_id)
                ->orderBy('column_position', 'ASC')
                ->with($relations)
                ->latest('id');
    }

    public function uploadFileTask($request, $task_id)
    {
        $filename = $this->uploadAttachment($request, 'files', ("Tasks/{$task_id}"));
        if ($filename) {
            try {
                $task = Task::find(1);
                $attach = TaskFile::create([
                    'task_id' => $task->id,
                    'files' => 'filename',
                    'uploaded_by' => 1

                ]);
            } catch (\Exception $ex) {
               // $this->deleteAttachment($filename);
                throw new CustomException($ex->getMessage(), 404);
            }
        } else {
            throw new CustomException('Your File Not Accepted', 404);
        }
        return $attach;
    }
}
