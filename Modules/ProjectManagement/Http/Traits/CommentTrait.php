<?php

namespace Modules\ProjectManagement\Http\Traits;

use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskComment;
use Modules\ProjectManagement\Http\Requests\StoreCommentRequest;

trait CommentTrait
{
    function add_comment(Task $task, StoreCommentRequest $request) {
        $store = TaskComment::create([
            'task_id' => $task->id,
            'comments' => $request->comments,
            'comment_by' => auth()->user()->id
        ]);
        return apiResponse(
            data: $store,
            message: 'Comment Added Successfully'
        );
    }
}
