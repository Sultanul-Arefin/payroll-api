<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class LeaveDetailsResources extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_leaves_id' => $this->user_leaves_id,
            'date' => $this->dates,
           // 'details' => LeaveDetailsResourcel::collection($this->whenLoaded('leave_details'))
        ];
    }
}
