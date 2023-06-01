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
            'id' => $this->project_column_id,
            'column' => $this->getColumnName($this->project_column),
            'tasks' => $this->tasks
            // 'tasks' => TaskResource::collection($this->tasks)
            // 'column' => $this->info($this, $this->project_column),
            // 'columnss' => ProjectColumnResource::collection($this->project_column)
        ];
    }

    public function getColumnName($column)
    {
        return $column->column_name;
    }
}
