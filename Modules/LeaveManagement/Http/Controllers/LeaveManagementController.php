<?php

namespace Modules\LeaveManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeaveManagement\Http\Requests\LeaveStoreRequest;
use Modules\LeaveManagement\Http\Resources\LeaveListResource;
use Modules\LeaveManagement\Http\Resources\LeaveResource;
use Modules\LeaveManagement\Http\Services\CompanyService;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\Company\Http\Traits\LeaveTrait;
use DateTime;
use DateInterval;
use DatePeriod;

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
    public function leave_store(LeaveStoreRequest $request){

        $existDates = $this->isHolidayExist($request->dates);//params pass to date for checking holiday exist or not
        $leaveStore = $this->leaveRepository->leave_store($request, $existDates);
        return apiResponse(null, 'Successfully Leave Stored', 'success', '201');
    }
    public function leave_list(){
       $LeaveList =  $this->leaveRepository->leave_list();
       $LeavesCollection =  LeaveListResource::collection($LeaveList);
        return apiResponse($LeavesCollection, 'Successfully Get Leave List', 'success', '200');
    }

}
