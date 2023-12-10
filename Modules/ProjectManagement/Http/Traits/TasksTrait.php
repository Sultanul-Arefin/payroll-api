<?php

namespace Modules\ProjectManagement\Http\Traits;

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
        }

        return $task;
    }
}
