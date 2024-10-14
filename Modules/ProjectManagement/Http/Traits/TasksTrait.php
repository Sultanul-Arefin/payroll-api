<?php

namespace Modules\ProjectManagement\Http\Traits;

use Illuminate\Http\Request;
use Modules\ProjectManagement\Entities\Task;

trait TasksTrait
{
    public function getTaskDetails($task)
    {
        foreach ($task as $key => $value) {
            $users = $value->associated_users;
            $asso_users = [];
            if (! is_null($users)) {
                foreach ($users as $user) {
                    array_push($asso_users, [
                        'name' => $user->user_info->name,
                        'email' => $user->user_info->email,
                        'id' => $user->user_info->id,
                    ]);
                }
            }
            $task[$key]['users'] = $asso_users;
            unset($task[$key]['associated_users']);

            // comments
            $comments = $value->comments;
            $asso_comments = [];
            if (! is_null($comments)) {
                foreach ($comments as $comment) {
                    array_push($asso_comments, [
                        'id' => $comment->id,
                        'comment' => $comment->comments,
                        'comment_by' => $comment->user->name,
                    ]);
                }
            }
            $task[$key]['task_comment'] = $asso_comments;
            unset($task[$key]['comments']);
            
            $task[$key]['total_comments'] = $value->comments->count();
            unset($task[$key]['comments']);
        }

        return $task;
    }

    function update_task_status(Task $task, Request $request) {
        $request->validate([
            'status' => 'required|integer|in:0,1,2,3'
        ]);
        $task->update([
            'status' => $request->status
        ]);
        return apiResponse(
            data: null,
            message: 'Task Status Updated Successfully'
        );
    }

    function update_task_percentage(Task $task, Request $request) {
        $request->validate([
            'percentage' => 'required|integer|min:1|max:100'
        ]);
        $task->update([
            'percentage' => $request->percentage
        ]);
        return apiResponse(
            data: null,
            message: 'Task Percentage Updated Successfully'
        );
    }
    
}
