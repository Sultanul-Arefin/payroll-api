<?php

namespace Modules\Payslip\Http\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\EmployeeSalaryItems\Entities\DeductionDetails;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class CategoryResource extends JsonResource
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
            // $this->merge(
            //     Arr::only(parent::toArray($request), [
            //         'id'
            //     ])
            // ),
            'category_id' => $this->id,
            'category_name' => $this->name,
            'amount' => $this->getAmount($this->id)
        ];
    }

    function getAmount($category_id) {
        if($category_id == 7){
            return [
                'employee_government_deduction' => 0.00,
                'other_complimentary_deduction' => 0.00,
                'employee_deduction' => 0.00
            ];
            return DeductionDetails::query()
                ->whereHas(
                    'employee_salary_item', function(Builder $builder)use($category_id){
                        $builder
                        ->where('company_id', auth()->user()->company_id)
                        ->where('employee_id', request('employee_id'))
                        ->whereHas(
                            'salaryItemsName', function(Builder $builder)use($category_id){
                                $builder->whereHas(
                                    'salaryItemsCategory', function(Builder $builder)use($category_id){
                                        $builder->where('id', $category_id);
                                    }
                                );
                            }
                        );
                    }
                )
                ->get()->sum('employee_amount as emp_amount');
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function(Builder $builder)use($category_id){
                        $builder->whereHas(
                            'salaryItemsCategory', function(Builder $builder)use($category_id){
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } elseif($category_id == 8){
            return [
                'company_government_contribution' => 0.00,
                'company_other_complimentary_contribution' => 0.00,
                'company_contribution' => 0.00
            ];
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function(Builder $builder)use($category_id){
                        $builder->whereHas(
                            'salaryItemsCategory', function(Builder $builder)use($category_id){
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } elseif($category_id == 1){
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function(Builder $builder)use($category_id){
                        $builder->where('name', 'Wages')
                            ->whereHas(
                                'salaryItemsCategory', function(Builder $builder)use($category_id){
                                    $builder->where('id', 1);
                                }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        } else{
            return EmployeeSalaryItem::query()
                ->whereHas(
                    'salaryItemsName', function(Builder $builder)use($category_id){
                        $builder->whereHas(
                            'salaryItemsCategory', function(Builder $builder)use($category_id){
                                $builder->where('id', $category_id);
                            }
                        );
                    }
                )
                ->where('company_id', auth()->user()->company_id)
                ->where('employee_id', request('employee_id'))
                ->get()->sum('amount');
        }
    }
}