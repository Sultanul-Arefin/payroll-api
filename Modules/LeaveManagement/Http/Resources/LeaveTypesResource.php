<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypesResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name ' => $this->name,
        ];
    }
}
