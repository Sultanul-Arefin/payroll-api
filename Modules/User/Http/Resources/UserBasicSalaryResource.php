<?php

namespace Modules\User\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class UserBasicSalaryResource extends JsonResource
{
    public $user_id;

    public function __construct($resource, $user_id)
    {
        parent::__construct($resource);
        $this->user_id = $user_id;
    }

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
                    'name',
                ])
            ),
            // 'user_id' => $this->user_id,
            'user' => $this->getUserInfo($this->user_id),
            'amount' => $this->employeeSalaryItem->where('employee_id', $this->user_id)->first()?->amount ?? 0,
            'is_general' => $this->employeeSalaryItem->where('employee_id', $this->user_id)->first()?->is_general ?? 0,
            'is_percentage' => $this->employeeSalaryItem->where('employee_id', $this->user_id)->first()?->is_percentage ?? 0,
            'category' => $this->salaryItemsCategory?->name,
            'time_month_hour' => null,
            'time_per' => null,
            'govt_amount' => $this->getGovernmentAmount(),
            'employee_amount' => $this->getEmployeeAmount(),
            'is_deletable' => $this->is_deletable($this->salaryItemsCategory),
            'employee_salary_item_id' => $this->getEmployeeSalaryItemId()
        ];
    }

    private function getGovernmentAmount()
    {
        if($this->salaryItemsCategory->id == 7 || $this->salaryItemsCategory->id == 8)
        {
            $get_employee_salary_item = EmployeeSalaryItem::query()
                                ->whereHas('salaryItemsName', function(Builder $builder){
                                    $builder->where('name', 'like', $this->name)
                                            ->where('company_id', auth()->user()->company_id);
                                })
                                ->where('employee_id', $this->user_id)
                                ->first();
            return $get_employee_salary_item?->deduction_details?->government_or_company_amount;
        }
        return 0;
    }

    private function getEmployeeAmount()
    {
        if($this->salaryItemsCategory->id == 7 || $this->salaryItemsCategory->id == 8)
        {
            $get_employee_salary_item = EmployeeSalaryItem::query()
                                ->whereHas('salaryItemsName', function(Builder $builder){
                                    $builder->where('name', 'like', $this->name)
                                            ->where('company_id', auth()->user()->company_id);
                                })
                                ->where('employee_id', $this->user_id)
                                ->first();
            return $get_employee_salary_item?->deduction_details?->employee_amount;
        }
        return 0;
    }

    private function getEmployeeSalaryItemId()
    {
        $get_employee_salary_item = EmployeeSalaryItem::query()
                                ->whereHas('salaryItemsName', function(Builder $builder){
                                    $builder->where('name', 'like', $this->name)
                                            ->where('company_id', auth()->user()->company_id);
                                })
                                ->where('employee_id', $this->user_id)
                                ->first();
        return $get_employee_salary_item->id;
    }

    private function is_deletable($category): bool
    {
        if($category->id == 1 || $category->id == 2)
        {
            return false;
        }
        return true;
    }

    private function getUserInfo($user_id)
    {
        $user = User::query()->where('id', $user_id)->first();
        return [
            'name' => $user->name,
            'id' => $user->id
        ];
    }
}
