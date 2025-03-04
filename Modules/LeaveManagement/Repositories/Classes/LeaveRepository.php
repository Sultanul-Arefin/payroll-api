<?php

namespace Modules\LeaveManagement\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\LeaveManagement\Entities\UserLeave;
use Modules\LeaveManagement\Entities\UserLeaveDetail;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;

class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface
{
    /**
     * LeaveSalaryItems Repository constructor.
     */
    public function __construct(LeaveSalaryItems $model)
    {
        parent::__construct($model);
    }

    /**
     * all salary item list show under a company id
     */
    public function leave_types(): ?Collection
    {
        return SalaryItemsName::query()
            ->whereHas('leave_salary_items')
            ->where('company_id', auth()->user()->company_id)
            ->get();
    }

    /**
     * @return mixed
     */
    public function leave_store($request, $existDates)
    {
        $mergeDates = array_merge($request->dates, $existDates); //user request dates and exist dates are merged
        $valueCounts = array_count_values($mergeDates); //each date count
        $sortDates = array_keys(array_filter($valueCounts, function ($count) {
            return $count === 1;
        }));
        $userLeave = DB::transaction(function () use ($request, $sortDates) {

            $image_path = null;
            if($request->file('files')){
                $file = $request->file('files');
                if($file){
                    $image_name = rand(10000, 50000).'_'.time().'.'.$file->extension();
                    $file->storeAs("uploads/leave_files/", $image_name, 'public');
                    $image_path = "storage/uploads/leave_files/".$image_name;
                }
            }
            $userLeave = UserLeave::create([
                'leave_type' => $request->leave_type,
                'user_id' => $request->user_id,
                'leave_message' => $request->leave_message,
                'files' => $image_path,
                'action_by' => 1,
            ]);

            foreach ($sortDates as $date) {
                UserLeaveDetail::create([
                    'user_leaves_id' => $userLeave->id,
                    'dates' => $date,
                ]);
            }

            return $userLeave;
        });

        return $userLeave;

    }

    public function leave_list()
    {
        return UserLeave::query()
            ->when(
                !is_null(request('user_id')),
                fn (Builder $builder) => $builder->where(function ($query) {
                    $query->where('user_id', request('user_id'));
                })
            )
            ->when(
                !is_null(request('department_id')) && is_null(request('user_id')),
                fn(Builder $builder) => $builder->whereHas(
                    'user', function(Builder $builder){
                        $builder->where('department_id', request('department_id'));
                    }
                )
            )
            ->whereHas(
                'user', function(Builder $builder){
                    $builder->where('company_id', auth()->user()->company_id);
                }
            )
            ->with('user', 'salary_item', 'leave_details')->get();
    }
}
