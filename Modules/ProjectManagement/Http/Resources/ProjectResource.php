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
            // 'project_data' => $this->getProjectData($this->id),
            'total_task' => $this->getTotalTask($this->id),
            'project_data' => $this->getProjectData($this->id),
            'is_editable' => $this->getIfEditable()
            // 'assigned_employees' => $this->project_associated_colums->each(function($item){
            //     return $item->tasks->each(function($emp_item){
            //         return $emp_item->associated_users->each(function($item){
            //             return $item->user_info;
            //         });
            //     });
            // })
        ];
    }

    private function getIfEditable(): bool
    {
        if(
            auth()->user()->role_id == User::ADMIN ||
            auth()->user()->role_id == User::MANAGER ||
            auth()->user()->id == $this->project_manager ||
            auth()->user()->id == $this->created_by
        ){
            return true;
        }
        return false;
    }

    function getProjectData($project_id) {
        $project_columns = ProjectAssociatedColumn::query()
            ->where('project_id', $project_id)
            ->orderBy('column_position', 'ASC')
            ->with([])
            ->latest('id')
            ->get();
        $final_data = [];
        $count_individual_task = Task::where('project_id', $project_id)->count();
        $completed_task = Task::where('project_id', $project_id)->where('status', Task::COMPLETED)->count();
        $backlog_task = Task::where('project_id', $project_id)->where('status', Task::BACKLOG)->count();
        $in_progress_task = Task::where('project_id', $project_id)->where('status', Task::IN_PROGRESS)->count();
        $cancelled_task = Task::where('project_id', $project_id)->where('status', Task::CANCELLED)->count();
        array_push($final_data, [
            'column' => 'Completed',
            'percentage' => $count_individual_task ? round(($completed_task/$count_individual_task) * 100) : 0,
            'task_count' => $completed_task,
            'color' => 'green'
        ]);
        array_push($final_data, [
            'column' => 'Backlog',
            'percentage' => $count_individual_task ? round(($backlog_task/$count_individual_task) * 100) : 0,
            'task_count' => $backlog_task,
            'color' => 'navy'
        ]);
        array_push($final_data, [
            'column' => 'In Progress',
            'percentage' => $count_individual_task ? round(($in_progress_task/$count_individual_task) * 100) : 0,
            'task_count' => $in_progress_task,
            'color' => 'orange'
        ]);
        array_push($final_data, [
            'column' => 'Cancelled',
            'percentage' => $count_individual_task ? round(($cancelled_task/$count_individual_task) * 100) : 0,
            'task_count' => $cancelled_task,
            'color' => 'red'
        ]);
        return $final_data;
    }

    function getTotalTask($project_id) {
        $count_task = Task::where('project_id', $project_id)->count();
        return $count_task;
    }

    // function getProjectData($project_id) {
    //     $tasks = ProjectAssociatedColumn::query()
    //         ->where('project_id', $project_id)
    //         ->orderBy('column_position', 'ASC')
    //         ->with([])
    //         ->latest('id')
    //         ->get();
    //     $final_data = [];
    //     foreach($tasks as $task){
    //         $count_task = Task::where('project_associated_column_id', $task->id)->count();
    //         array_push($final_data, [
    //             'column' => $task->project_column_name,
    //             'total_task' => $count_task
    //         ]);
    //     }
    //     return $final_data;
    // }

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
                    $unique_employees = [];
                    foreach ($task_associated_employees as $value) {
                        $user = User::where('id', $value->user_id)->first();
                        array_push($unique_employees, $user);
                    }
                    if($unique_employees){
                        $unique_employees = collect($unique_employees)->unique('id')->values();
                        foreach($unique_employees as $uv)
                        {
                            array_push(
                                $employees, [
                                    'user_name' => $uv?->name,
                                    'user_image' => $uv?->user_details?->changed_user_image,
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $employees;
    }
}
