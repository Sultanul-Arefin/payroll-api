<?php

namespace Modules\LeaveManagement\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Company\Http\Traits\LeaveTrait;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\LeaveManagement\Http\Requests\LeaveStoreRequest;
use Modules\LeaveManagement\Http\Resources\LeaveListResource;
use Modules\LeaveManagement\Http\Resources\LeaveResource;
use Modules\LeaveManagement\Notifications\LeaveManagementNotification;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\ProjectManagement\Http\Controllers\ProjectController;
use Modules\TimeManagement\Http\Resources\TimeManagementResource;

class LeaveManagementController extends Controller
{
    use LeaveTrait;

    public function __construct(
        public LeaveRepositoryInterface $leaveRepository
    ) {

    }

    public function leave_types()
    {

        $leaveTypes = $this->leaveRepository->leave_types();
        $sortData = LeaveResource::collection($leaveTypes);

        return apiResponse($sortData, 'Data successfully fetched', 'success', '200');
    }

    public function leave_store(LeaveStoreRequest $request)
    {
        $request['user_id'] = $request->user_id ?? auth()->user()->id;
        $taken_leave = UserLeaveDetail::query()
                    ->whereHas(
                        'user_leave', function(Builder $builder) use($request){
                            $builder->where('user_id', $request->user_id);
                        }
                    )
                    ->whereIn('dates', $request->dates)
                    ->count();
        if($taken_leave > 0)
        {
            return apiResponse(
                data: null,
                message: 'Date Already Taken',
                status: 'error',
                statusCode: 422
            );
        }
        $existDates = $this->isHolidayExist($request->dates); //params pass to date for checking holiday exist or not
        $leaveStore = $this->leaveRepository->leave_store($request, $existDates);

        // STORE LEAVE NOTIFICATION
        $requested_leave_user = $this->getRequestedUser($request->user_id);
        $admin_user = app(ProjectController::class)->getAdminUser();
        $data = [
            'title' => 'Leave Request Created',
            'description' => "A New Leave Request Has Been Created For <b>{$requested_leave_user->name}</b>",
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Leave Management',
            'color' => '',
        ];
        $admin_user->notify(new LeaveManagementNotification($data));

        return apiResponse(null, 'Successfully Leave Stored', 'success', '201');
    }

    public function getRequestedUser($user_id): User
    {
        return User::where('id', $user_id)->first();
    }

    public function leave_list()
    {
        $LeaveList = $this->leaveRepository->leave_list();
        $LeavesCollection = LeaveListResource::collection($LeaveList);

        return apiResponse(
            data: $LeavesCollection,
            message: 'Successfully Get Leave List'
        );
    }

    public function leave_status(Request $request)
    {
        $request->validate([
            'user_leave_id' => 'required|exists:user_leaves,id',
            'status' => 'required|in:1,3',
            'action_message' => 'required',
        ]);
        try {
            $userLeave = UserLeave::findOrFail($request->user_leave_id);
            $update = $userLeave->update([
                'status' => $request->status,
                'action_message' => $request->action_message,
                'action_by' => auth()->user()->id,
            ]);
            if ($update) {
                // STORE LEAVE NOTIFICATION
                $get_user_id = UserLeave::query()
                            ->where('id', $request->user_leave_id)
                            ->first();
                $requested_leave_user = $this->getRequestedUser($get_user_id->user_id);

                $status = $this->getLeaveStatus($request->status);
                $logged_in_user_name = auth()->user()->name;

                $data = [
                    'title' => 'Leave Request Approved',
                    'description' => "Leave Request Has Been <b>{$status}</b> By <b>{$logged_in_user_name}</b>", // DATA DIDN'T SAVE AS EXPECTED. HAVE TO WORK
                    'action' => [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                    ],
                    'action_at' => date('Y-m-d H:i:s'),
                    'type' => 'Leave Management',
                    'color' => '',
                ];
                $requested_leave_user->notify(new LeaveManagementNotification($data));

                return apiResponse(null, 'Successfully Leave Updated', 'success', '200');
            } else {
                throw new \Exception('');
            }

        } catch (\Exception $ex) {
            throw new CustomException('oops! something wrong, please try again', 404);
        }
    }

    public function getLeaveStatus($status)
    {
        switch ($status) {
            case UserLeave::APPROVED : return "Approved";
                break;
            case UserLeave::DENIED : return "Denied";
                break;
            case UserLeave::PENDING : return "Pending";
                break;
            default: return false;
        }
    }

    public function leave_count()
    {
        return apiResponse(
            data: new TimeManagementResource(auth()->user())
        );
    }
}
