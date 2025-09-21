<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffTaskDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
            'main_points' => StaffTaskMainPointsResource::collection($this->main_points)
        ];
    }
}
