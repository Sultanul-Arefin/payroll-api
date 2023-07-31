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
            'leave_status' => $this->status,
            'leave_type_name' => $this->leave_types->name,
            'employee_name' => $this->user->name,
            'employee_email' => $this->user->email,
            'leave_details' => LeaveDetailsResources::collection($this->whenLoaded('leave_details')),
            'employee_department' => $this->getDepartment($this->user?->department?->only('department_name'))
        ];
    }

    function getDepartment($department_details) {
        if($department_details == null){
            return null;
        }
        if($department_details['department_name']){
            return $department_details['department_name'];
        }
    }
}
