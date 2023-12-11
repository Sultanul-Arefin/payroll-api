<?php

namespace Modules\ProjectManagement\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Notification\Entities\Notification;
use Modules\Notification\Http\Resources\NotificationResource;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;

class ProjectOverviewResource extends JsonResource
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
            'project_data' => $this->getProjectData($this->id),
            'total_task' => $this->getTotalTask($this->id),
            'activity' => $this->getActivity($this->id)
        ];
    }

    function getActivity($project_id) {
        $project = Project::where('id', $project_id)->first();
        return NotificationResource::collection(
            $project
                ?->notifications()
                ->when(! is_null(request('search')), function ($query) {
                    $query->where(
                        'data',
                        'LIKE',
                        '%'.request('search').'%'
                    );
                })
                ->latest('created_at')
                ->cursorPaginate()
            );
    }

    function getTotalTask($project_id) {
        $count_task = Task::where('project_id', $project_id)->count();
        return $count_task;
    }

    function getProjectData($project_id) {
        $project_columns = ProjectAssociatedColumn::query()
            ->where('project_id', $project_id)
            ->orderBy('column_position', 'ASC')
            ->with([])
            ->latest('id')
            ->get();
        $final_data = [];
        // foreach($project_columns as $project_column){
        //     $count_task = Task::where('project_associated_column_id', $project_column->id)->count();
        //     $count_individual_task = Task::where('project_id', $project_id)->count();
        //     $completed_task = Task::where('project_id', $project_id)->where('status', Task::COMPLETED)->count();
        //     $backlog_task = Task::where('project_id', $project_id)->where('status', Task::BACKLOG)->count();
        //     $in_progress_task = Task::where('project_id', $project_id)->where('status', Task::IN_PROGRESS)->count();
        //     $cancelled_task = Task::where('project_id', $project_id)->where('status', Task::CANCELLED)->count();
        //     array_push($final_data, [
        //         'column' => $project_column->project_column_name,
        //         'percentage' => $count_individual_task ? $count_task/$count_individual_task : 0,
        //         'total_task' => $count_task
        //     ]);
        // }
        $count_individual_task = Task::where('project_id', $project_id)->count();
        $completed_task = Task::where('project_id', $project_id)->where('status', Task::COMPLETED)->count();
        $backlog_task = Task::where('project_id', $project_id)->where('status', Task::BACKLOG)->count();
        $in_progress_task = Task::where('project_id', $project_id)->where('status', Task::IN_PROGRESS)->count();
        $cancelled_task = Task::where('project_id', $project_id)->where('status', Task::CANCELLED)->count();
        array_push($final_data, [
            'column' => 'Completed',
            'percentage' => $count_individual_task ? round(($completed_task/$count_individual_task) * 100) : 0,
            'total_task' => $count_individual_task
        ]);
        array_push($final_data, [
            'column' => 'Backlog',
            'percentage' => $count_individual_task ? round(($backlog_task/$count_individual_task) * 100) : 0,
            'total_task' => $count_individual_task
        ]);
        array_push($final_data, [
            'column' => 'In Progress',
            'percentage' => $count_individual_task ? round(($in_progress_task/$count_individual_task) * 100) : 0,
            'total_task' => $count_individual_task
        ]);
        array_push($final_data, [
            'column' => 'Cancelled',
            'percentage' => $count_individual_task ? round(($cancelled_task/$count_individual_task) * 100) : 0,
            'total_task' => $count_individual_task
        ]);
        return $final_data;
    }
}
