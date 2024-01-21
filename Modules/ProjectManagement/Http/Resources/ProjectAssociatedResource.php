<?php

namespace Modules\ProjectManagement\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;
use Modules\ProjectManagement\Http\Controllers\TaskController;

class ProjectAssociatedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'column' => $this->project_column_name,
            'column_position' => $this->column_position,
            'tasks' => app(TaskController::class)->getTaskDetails($this->tasks),
            'task_count' => $this->getTaskCount($this->tasks)
        ];
    }

    function getTaskCount($tasks) {
        return $tasks->count();
    }

    function test_data() {
        return 'dummy data';
    }

    public function getTasks($tasks)
    {

        $tasks->each(function ($item) {
            // return $item->associated_users;
            $item->associated_users->map(function ($name) {
                return $name->user_info->only('id', 'name');
            });
        });

        return $tasks;
        $tasks->put('a', 'b');

        return $tasks;

        return [
            'tasks' => $tasks,
        ];
    }

    public function getColumnName($column)
    {
        return $column->column_name;
    }
}
