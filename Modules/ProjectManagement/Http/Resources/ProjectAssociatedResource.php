<?php

namespace Modules\ProjectManagement\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\ProjectManagement\Http\Controllers\TaskController;

class ProjectAssociatedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'column' => $this->project_column_name,
            // 'column' => $this->getColumnName($this->project_column),
            // 'taskssss' => $this->tasks,
            // 'tasks' => $this->getTasks($this->tasks),
            'tasks' => app(TaskController::class)->getTaskDetails($this->tasks)
            // 'tasks' => $this->tasks
            // 'tasks' => TaskResource::collection($this->tasks)
            // 'column' => $this->info($this, $this->project_column),
            // 'columnss' => ProjectColumnResource::collection($this->project_column)
        ];
    }

    function getTasks($tasks) {
        
        $tasks->each(function($item){
            // return $item->associated_users;
            $item->associated_users->map(function($name){
                return $name->user_info->only('id', 'name');
            });
        });
        return $tasks;
        $tasks->put('a', 'b');
        return $tasks;
        return [
            'tasks' => $tasks
        ];
    }

    public function getColumnName($column)
    {
        return $column->column_name;
    }
}
