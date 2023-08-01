<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Modules\LeaveManagement\Entities\UserLeave;

class LeaveListResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'leave_status' => $this->status === UserLeave::APPROVED 
                                    ? 'Approved' : ($this->status === UserLeave::PENDING
                                    ? 'Pending' : 'Denied'),
            'leave_type_name' => $this->salary_item->name,
            'employee_name' => $this->user->name,
            'employee_email' => $this->user->email,
            'employee_leave_message' => $this->leave_message,
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
