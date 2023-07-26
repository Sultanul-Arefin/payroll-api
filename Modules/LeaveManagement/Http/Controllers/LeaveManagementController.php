<?php

namespace Modules\LeaveManagement\Http\Controllers;

use App\Exceptions\CustomException;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mockery\Exception;
use Modules\LeaveManagement\Entities\UserLeave;
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
    public function leave_status(Request $request){
        $request->validate([
            'user_leave_id' => 'required',
            'status' => 'required|in:1,3',
            'action_message' => 'required'
        ]);
        try {
            $userLeave = UserLeave::findOrFail($request->user_leave_id);
            $update =  $userLeave->update([
                'status' => $request->status,
                'action_message' => $request->action_message,
                'action_by' => auth()->user()->id
            ]);
            if($update){
                return apiResponse(null, 'Successfully Leave Updated', 'success', '200');
            }else{
                throw new \Exception('');
            }

        }catch (\Exception $ex){
            throw new CustomException('oops! something wrong, please try again', 404);
        }
    }

}
