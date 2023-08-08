<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class EmployeeResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'totalEmployee' => $this->id,
        ];
    }
}
