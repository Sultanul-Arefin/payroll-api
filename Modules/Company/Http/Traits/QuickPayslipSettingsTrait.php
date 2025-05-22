<?php

namespace Modules\Company\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

trait QuickPayslipSettingsTrait
{
    function view_payslip_settings()
    {
        $settings = auth()->user()->company->only(['no_of_working_days_per_week', 'working_hours_per_day', 'lunch_and_others_per_day', 'working_hours_per_week']);
        return apiResponse(
            data: $settings
        );
    }

    function add_payslip_settings(Request $request)
    {
        $request->validate([
            'no_of_working_days_per_week' => 'required|numeric|max:7',
            'working_hours_per_day' => 'required|numeric|max:23',
            'lunch_and_others_per_day' => 'required|numeric|max:23',
            'working_hours_per_week' => 'required|numeric'
        ]);

        $update = auth()->user()->company->update([
            'no_of_working_days_per_week' => $request->no_of_working_days_per_week, 
            'working_hours_per_day' => $request->working_hours_per_day,
            'lunch_and_others_per_day' => $request->lunch_and_others_per_day,
            'working_hours_per_week' => $request->working_hours_per_week
        ]);

        $company_info = [
            'company_id' => auth()->user()->company?->id,
            'company_logo' => auth()->user()->company->changed_company_logo,
            'company_name' => auth()->user()->company->company_name,
            'no_of_working_days_per_week' => auth()->user()->company->no_of_working_days_per_week,
            'working_hours_per_day' => auth()->user()->company->working_hours_per_day
        ];

        return apiResponse(
            data: $company_info,
            message: 'Payslip Settings Updated Successfully'
        );
    }

    

    function view_leave_days()
    {
        $salary_items = SalaryItemsName::query()
                ->whereHas(
                    'leave_salary_items'
                )
                ->where('company_id', auth()->user()->company_id)
                ->get();
        $filtered_data = $salary_items->filter(function($item){
            return $item->name == 'Annual Leave' || $item->name == 'Sick Leave';
        })->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'count' => $item->leave_salary_items?->no_of_days, // Get count directly without returning the relationship
            ];
        });
        return apiResponse(
            data: $filtered_data
        );
    }

    function add_leave_days(Request $request)
    {
        $request->validate([
            'no_of_sick_leaves' => 'required|integer|min:0|max:50',
            'no_of_annual_leaves' => 'required|integer|min:0|max:50',
        ]);
        $update_sick = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder->where('company_id', auth()->user()->company_id)
                        ->where('name', 'Sick Leave');
                }
            )->update([
                'no_of_days' => $request->no_of_sick_leaves
            ]);
        $update_annual = LeaveSalaryItems::query()
            ->whereHas(
                'salary_items_name', function(Builder $builder){
                    $builder->where('company_id', auth()->user()->company_id)
                        ->where('name', 'Annual Leave');
                }
            )->update([
                'no_of_days' => $request->no_of_annual_leaves
            ]);

        return apiResponse(
            data: null,
            message: 'Leaves Updated Successfully'
        );
    }
}