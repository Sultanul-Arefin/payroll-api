<?php

namespace Modules\Permission\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Permission\Entities\Permission;
use Modules\Permission\Http\Resources\ChildPermissionResource;

class PermissionResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->name,
            'children' => ChildPermissionResource::collection(Permission::where('parent_id', $this->id)->get())
        ];
    }
}
