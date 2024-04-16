<?php

namespace Modules\ProjectManagement\Http\Resources;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\ProjectManagement\Entities\Task;

class TaskOverviewResource extends JsonResource
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
                ])
            ),
            'title' => $this->task_title,
            'estimated_hour' => $this->estimation_hour,
            'start_date' => $this->start_date_time,
            'end_date' => $this->end_date_time,
            'status' => $this->status,
            'color' => $this->getColor($this->status),
            'percentage' => $this->percentage,
            'employees' => $this->get_employees($this->associated_users),
            // 'percentage' => $this->getPercentage($this->end_date_time, date('Y-m-d', strtotime($this->created_at)))
        ];
    }

    private function get_employees($associated_users)
    {
        $users = $associated_users->each(function($user){
            return $user->user_info;
        });
        foreach($users as $user){
            $user->name = $user->user_info['name'];
            unset($user->id);
            unset($user->task_id);
            unset($user->user_id);
            unset($user->created_at);
            unset($user->updated_at);
            unset($user->user_info);
        }
        return $users;
    }

    function getPercentage($end_date, $created_date) {
        if(!isset($end_date)){
            return 0;
        }
        $end_date = new DateTime($end_date);
        $created_date = new DateTime($created_date);
        $interval = $end_date->diff($created_date);
        return $interval->format('%R%a');
    }

    function getColor($status) {
        return match ($status) {
            Task::BACKLOG => 'navy',
            Task::COMPLETED => 'green',
            Task::IN_PROGRESS => 'orange',
            Task::CANCELLED => 'red',
            default => ''
        };
    }
}
