<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class SalaryItemsCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                ])
            ),
            "category_$this->id" => EmployeeSalaryItemsResource::collection(
                $this->getCategoryItems($this->id)
            ),
        ];
    }

    public function getCategoryItems($category_id)
    {
        return EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) use ($category_id) {
                    $builder->whereHas(
                        'salaryItemsCategory', function (Builder $builder) use ($category_id) {
                            $builder->where('id', $category_id);
                        }
                    );
                }
            )
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', request('employee_id'))
            ->get();
    }
}
