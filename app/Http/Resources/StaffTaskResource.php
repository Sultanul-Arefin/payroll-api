<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StaffTaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'created_by' => new UserResource($this->createdBy),
            'manager' => new UserResource($this->manager),
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'employees' => UserResource::collection($this->employees),
        ];
    }
}
