<?php

namespace Modules\Package\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class PackageResource extends JsonResource
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
                    'package_name'
                ])
            ),
            'max_department_count' => $this->no_of_departments,
            'max_employee_count' => $this->no_of_employees,
            'max_payslip_count' => $this->no_of_payslips,
            'created_at' => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}
