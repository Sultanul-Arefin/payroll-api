<?php

namespace Modules\LeaveManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeaveManagement\Http\Resources\LeaveResource;
use Modules\LeaveManagement\Http\Services\LeaveService;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\Company\Http\Traits\LeaveTrait;

class LeaveManagementController extends Controller
{
    use LeaveTrait;
    public function __construct(
        public LeaveRepositoryInterface $leaveRepository
    ) {

    }
    public function leave_types(){
        $leaveTypes = $this->leaveRepository->leave_types();
        $sortData = LeaveResource::collection($leaveTypes);

        return apiResponse($sortData, 'data successfully fetched', 'success', '200');

    }

}
