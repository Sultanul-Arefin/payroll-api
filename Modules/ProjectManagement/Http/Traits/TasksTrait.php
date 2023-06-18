<?php

namespace Modules\ProjectManagement\Http\Traits;

trait TasksTrait{
    function getTaskDetails($task){
        foreach($task as $key => $value){
            $users = $value->associated_users;
            $asso_users = [];
            if(!is_null($users)){
                foreach($users as $user){
                    array_push($asso_users, [
                        'name' => $user->user_info->name,
                        'email' => $user->user_info->email,
                        'id' => $user->user_info->id
                    ]);
                }
            }
            $task[$key]['users'] = $asso_users;
            unset($task[$key]['associated_users']);
        }
        return $task;
    }
}