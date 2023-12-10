<?php

namespace Modules\ProjectManagement\Http\Traits;

use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskComment;
use Modules\ProjectManagement\Http\Requests\StoreCommentRequest;
use Modules\ProjectManagement\Notifications\ProjectManagementNotification;

trait CommentTrait
{
    function add_comment(Task $task, StoreCommentRequest $request) {
        $store = TaskComment::create([
            'task_id' => $task->id,
            'comments' => $request->comments,
            'comment_by' => auth()->user()->id
        ]);
        // TASK COMMENT CREATION NOTIFICATION
        $project = $task->project_associated_column->project;
        $data = [
            'title' => 'Comment Created',
            'description' => 'A New Comment Has Been Created in Task '. $task->task_title,
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => '',
        ];
        $project->notify(new ProjectManagementNotification($data));
        return apiResponse(
            data: $store,
            message: 'Comment Added Successfully'
        );
    }
}
