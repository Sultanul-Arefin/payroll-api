<?php
namespace Modules\LeaveManagement\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;



class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface {

    /**
     * LeaveSalaryItems Repository constructor.
     *
     * @param LeaveSalaryItems $model
     */
    public function __construct(LeaveSalaryItems $model)
    {
        parent::__construct($model);
    }

    /**
     * all salary item list show under a company id
     * @return Collection|null
     */
    public function leave_types():?Collection
    {
       return SalaryItemsName::where('company_id', auth()->user()->company_id)->get();
    }

    /**
     * @param $request
     * @param $existDates
     * @return mixed
     */
    public function leave_store($request, $existDates)
    {
        $mergeDates = array_merge($request->dates, $existDates); //user request dates and exist dates are merged
        $valueCounts = array_count_values($mergeDates); //each date count
        $sortDates = array_keys(array_filter($valueCounts, function ($count){
           return $count === 1;
        }));
        $userLeave = DB::transaction(function () use($request, $sortDates){

           $userLeave = UserLeave::create([
                'leave_type' => $request->leave_type,
                'user_id' => auth()->user()->id,
                'leave_message' => $request->leave_message,
                'action_by' => 1,
            ]);

            foreach($sortDates as $date){
                UserLeaveDetail::create([
                    'user_leaves_id' => $userLeave->id,
                    'dates' => $date
                ]);
            }
            return $userLeave;
        });
        return $userLeave;

    }
    public function leave_list(){
       return UserLeave::with('user', 'salary_item', 'leave_details')->get();
    }

}
