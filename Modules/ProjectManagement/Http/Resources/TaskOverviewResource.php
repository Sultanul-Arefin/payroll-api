<?php

namespace Modules\ProjectManagement\Http\Resources;

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
            'percentage' => $this->getPercentage($this->id)
        ];
    }

    function getPercentage($task_id) {
        return $task_id;
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
