<?php

namespace Modules\Attendance\Http\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;
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