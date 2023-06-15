<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attendance\Http\Services\LeaveService;

class LeaveController extends Controller
{
    function __construct(
        public LeaveService $leaveService
    ) {
        
    }

    function leave_types() {
        $leaves = $this->leaveService->get_leave_types();
        return $leaves[0]->salary_items_name;
    }
}
