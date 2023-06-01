<?php

namespace Modules\ProjectManagement\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class ProjectAssociatedResource extends JsonResource
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
            'column' => $this->info($this, $this->project_column),
            // 'columnss' => ProjectColumnResource::collection($this->project_column)
            // 'tasks' => TaskResource::collection($this->)
        ];
    }

    public function info($all_info, $column_name)
    {
        return [
            'column_name' => $column_name->column_name,
            // 'tasks' => TaskResource::collection()
        ];
    }
}
