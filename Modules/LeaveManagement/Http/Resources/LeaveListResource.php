<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class LeaveListResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'leave_type' => $this->leave_type,
            'user_id' => $this->user_id,
            'leave_type_name' => $this->leave_types->name,
            'employee_name' => $this->user->name,
            'employee_email' => $this->user->email,
            'leave_details' => LeaveDetailsResources::collection($this->whenLoaded('leave_details'))
        ];
    }
}
