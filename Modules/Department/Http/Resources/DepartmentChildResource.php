<?php

namespace Modules\Department\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class DepartmentChildResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'department_name'
                ])
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'child' => DepartmentChildResource::collection($this->departments)
        ];
    }
}
