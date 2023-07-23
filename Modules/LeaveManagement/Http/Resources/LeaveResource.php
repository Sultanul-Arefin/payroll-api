<?php

namespace Modules\LeaveManagement\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;

class LeaveResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name ' => $this->name,
           // 'types' => new LeaveTypesResource($this->whenLoaded('salary_items_name'))
        ];
    }
}
