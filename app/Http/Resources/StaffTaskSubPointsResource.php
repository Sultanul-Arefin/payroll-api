<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\StaffTaskSubPoint;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffTaskSubPointsResource extends JsonResource
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
        ];
    }

    public function get_signed_off_by_manager($signed_off)
    {
        return match($signed_off)
        {
            StaffTaskSubPoint::SINGED_OFF => "yes",
            StaffTaskSubPoint::NOT_SINGED_OFF => "no",
            default => null
        };
    }

    public function get_progress($progress_status)
    {
        return match($progress_status){
            StaffTaskSubPoint::COMPLETE => "complete",
            StaffTaskSubPoint::INCOMPLETE => "incomplete",
            StaffTaskSubPoint::PENDING => "pending",
            default => null
        };
    }
}
