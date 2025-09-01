<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use JsonSerializable;
use App\Models\Support;
use Carbon\CarbonPeriod;
use App\Models\RotaShift;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use Modules\LeaveManagement\Entities\UserLeave;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'name'
                ])
            ),
            'total_shifts' => $this->get_total_shifts(),
            'total_working_time' => $this->get_total_working_time(),
            'dates' => $this->getShiftData(),
        ];
    }

    public function get_total_working_time()
    {
        $shifts = RotaShift::query()
                ->whereBetween('date', [request('start_date'), request('end_date')])
                ->get();

        if(count($shifts) > 0)
        {
            $totalMinutes = 0;
    
            foreach ($shifts as $shift) {
                $start = Carbon::createFromFormat('H:i', $shift->start_time);
                $end   = Carbon::createFromFormat('H:i', $shift->end_time);
    
                $totalMinutes += $start->diffInMinutes($end);
            }
    
            $totalHours = floor($totalMinutes / 60);
            $remainingMinutes = $totalMinutes % 60;
    
            return $totalHours . "Hours " . $remainingMinutes . "Minutes";
        }
        return "0Hour 0Minute";

        echo "Total time: {$totalHours} hours {$remainingMinutes} minutes";
    }

    public function get_total_shifts()
    {
        return RotaShift::query()
                ->whereBetween('date', [request('start_date'), request('end_date')])
                ->count();
    }

    public function getShiftData()
    {
        $period = CarbonPeriod::create(request('start_date'), request('end_date'));
        $dates = [];
        foreach($period as $date)
        {
            $key = $date->format('Y-m-d');

            // Query shifts for that day
            $shifts = RotaShift::query()
                ->whereDate('date', $key)
                ->where('employee_id', $this->id)
                ->select('id', 'start_time', 'end_time', 'break', 'notes', 'sent_notification', 'published')
                ->get()
                ->toArray();
    
            $dates[] = [
                $key => [
                    'shifts' => $shifts,
                    'is_holiday' => $this->checkIfHoliday($key),
                    'is_leave' => $this->checkIfLeave($key, $this->id),
                ]
            ];
        }
        return $dates;
    }

    public function checkIfLeave($date, $employee_id)
    {
        $leave = UserLeave::where('user_id', $employee_id)
            ->whereHas('leave_details', function ($query) use ($date) {
                $query->whereDate('dates', $date);
            })
            ->first();
        if($leave) {
            return $leave->salary_item?->name;
        }
        return false;
    }

    public function checkIfHoliday($date)
    {
        $dayName = strtolower(Carbon::parse($date)->format('l')); // e.g. 'saturday'

        $weeklyOffDays = [
            'saturday'  => $this->rota_work_schedule?->saturday_off,
            'sunday'    => $this->rota_work_schedule?->sunday_off,
            'monday'    => $this->rota_work_schedule?->monday_off,
            'tuesday'   => $this->rota_work_schedule?->tuesday_off,
            'wednesday' => $this->rota_work_schedule?->wednesday_off,
            'thursday'  => $this->rota_work_schedule?->thursday_off,
            'friday'    => $this->rota_work_schedule?->friday_off,
        ];

        return !empty($weeklyOffDays[$dayName]) ? 1 : 0;
    }
}
