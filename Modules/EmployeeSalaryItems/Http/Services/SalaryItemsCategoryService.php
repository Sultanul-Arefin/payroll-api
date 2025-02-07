<?php

namespace Modules\EmployeeSalaryItems\Http\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

class SalaryItemsCategoryService
{
    public function get_salary_items_name_with_employee(
        $salary_items_category_id,
        $count
    ) {
        return EmployeeSalaryItem::query()
            ->when($salary_items_category_id == 1, function ($query) use($salary_items_category_id){
                $query->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($salary_items_category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('salary_items_category_id', $salary_items_category_id)
                            ->whereNotIn('name', ['Absent Rate', 'Absent', 'Unpaid Sick Leave', 'Unpaid Sick Leave Rate', 'Sick Leave']);
                    }
                );
            })
            ->when($salary_items_category_id == 2, function ($query) use($salary_items_category_id){
                $query->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($salary_items_category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('salary_items_category_id', 1)
                            ->whereIn('name', ['Absent Rate', 'Unpaid Sick Leave Rate']);
                    }
                );
            })
            ->when($salary_items_category_id != 1 && $salary_items_category_id != 2, function ($query) use($salary_items_category_id){
                $query->whereHas(
                    'salaryItemsName', function (Builder $builder) use ($salary_items_category_id) {
                        $builder
                            ->where('company_id', auth()->user()->company_id)
                            ->where('salary_items_category_id', $salary_items_category_id);
                    }
                );
            })
            // ->whereHas(
            //     'salaryItemsName', function (Builder $builder) use ($salary_items_category_id) {
            //         $builder
            //             ->where('company_id', auth()->user()->company_id)
            //             ->where('salary_items_category_id', $salary_items_category_id)
            //             ->whereNotIn('name', ['Annual Leave', 'Sick Leave']);
            //     }
            // )
            ->latest()
            ->cursorPaginate($count);
    }
}
