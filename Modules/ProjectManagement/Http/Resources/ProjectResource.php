<?php

namespace Modules\ProjectManagement\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;

class ProjectResource extends JsonResource
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
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'project_title',
                    'project_description',
                ])
            ),
            'assigned_employees' => $this->getAssignedEmployees($this->id),
            'last_update' => $this->getLastUpdate($this->id),
            'status' => $this->getStatus($this->is_completed),
            'project_data' => $this->getProjectData($this->id)
            // 'assigned_employees' => $this->project_associated_colums->each(function($item){
            //     return $item->tasks->each(function($emp_item){
            //         return $emp_item->associated_users->each(function($item){
            //             return $item->user_info;
            //         });
            //     });
            // })
        ];
    }

    function getProjectData($project_id) {
        $tasks = ProjectAssociatedColumn::query()
            ->where('project_id', $project_id)
            ->orderBy('column_position', 'ASC')
            ->with([])
            ->latest('id')
            ->get();
        $final_data = [];
        foreach($tasks as $task){
            $count_task = Task::where('project_id', $project_id)->count();
            array_push($final_data, [
                'column' => $task->project_column_name,
                'total_task' => $count_task
            ]);
        }
        return $final_data;
    }

    function getStatus($status) {
        if($status == Project::COMPLETED){
            return 'completed';
        } elseif($status == Project::PROCESSING){
            return 'processing';
        }
        return 'cancelled';
    }

    function getLastUpdate($project_id) {
        return date('Y-m-d H:i:s');
    }

    public function getAssignedEmployees($project_id)
    {
        $employees = [];
        $tasks = Task::where('project_id', $project_id)->get();
        if ($tasks) {
            foreach ($tasks as $value) {
                $task_associated_employees = TaskAssociatedEmployee::where('task_id', $value->id)->get();
                if ($task_associated_employees) {
                    foreach ($task_associated_employees as $value) {
                        $user = User::where('id', $value->id)->first();
                        array_push(
                            $employees, [
                                'user_name' => $user?->name,
                                'user_image' => $user?->user_details?->changed_user_image,
                            ]
                        );
                    }
                }
            }
        }

        return $employees;
    }
}
