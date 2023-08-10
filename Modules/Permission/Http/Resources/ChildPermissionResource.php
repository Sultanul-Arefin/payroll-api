<?php

namespace Modules\Permission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class ChildPermissionResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->name,
        ];
    }
}
