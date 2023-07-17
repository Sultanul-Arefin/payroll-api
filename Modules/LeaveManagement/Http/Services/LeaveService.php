<?php

namespace Modules\LeaveManagement\Http\Services;

use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class LeaveService{
    function get_leave_types() {
        return LeaveSalaryItems::query()
            ->whereRelation(
                'salary_items_name',
                'company_id',
                auth()->user()->company_id
            )
            ->get();
    }
}
