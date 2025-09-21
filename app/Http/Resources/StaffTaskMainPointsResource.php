<?php

namespace App\Http\Resources;

use App\Models\StaffTaskMainPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffTaskMainPointsResource extends JsonResource
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
            'manager' => new UserResource($this->manager),
            'assigned' => new UserResource($this->assigned),
            'given_date' => $this->given_date,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'progress' => $this->get_progress($this->progress),
            'estimated_time' => $this->estimated_time,
            'signed_off_by_manager' => $this->get_signed_off_by_manager($this->signed_off_by_manager),
            'signed_off_time' => $this->signed_off_time,
            'sub_points' => StaffTaskSubPointsResource::collection($this->sub_points)
        ];
    }

    public function get_signed_off_by_manager($signed_off)
    {
        return match($signed_off)
        {
            StaffTaskMainPoint::SINGED_OFF => "yes",
            StaffTaskMainPoint::NOT_SINGED_OFF => "no",
            default => null
        };
    }

    public function get_progress($progress_status)
    {
        return match($progress_status){
            StaffTaskMainPoint::COMPLETE => "complete",
            StaffTaskMainPoint::INCOMPLETE => "incomplete",
            StaffTaskMainPoint::PENDING => "pending",
            default => null
        };
    }
}
