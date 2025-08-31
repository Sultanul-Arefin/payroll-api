<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Http\Resources\ShiftSummaryResource;
use App\Http\Traits\RotaLocation;
use App\Http\Traits\RotaWorkSchedule;
use App\Models\RotaShift;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RotaManagementController extends Controller
{
    use RotaLocation, RotaWorkSchedule;

    public function shifts(Request $request)
    {
        $request->validate([
            'start_date'    => 'required|date|date_format:Y-m-d',
            'end_date'      => 'required|date|date_format:Y-m-d'
        ]);
        $user = User::query()
            ->when(
                !is_null(request('department_id')),
                fn (Builder $builder) => $builder->where(function ($query) use($request){
                    $query->where('department_id', $request->department_id);
                })
            )
            ->where('company_id', auth()->user()->company_id)
            ->where('status', User::USER_ACTIVE)
            ->get();
        return apiResponse(
            data: ShiftResource::collection(
                $user
            )
        );
    }

    protected function calculateTotalHour(Collection $shifts)
    {
        $totalMinutes = 0;

        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift->start_time);
            $end = Carbon::parse($shift->end_time);
            $totalMinutes += $start->diffInMinutes($end); // adding durations
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return "{$hours}Hours {$minutes}Minutes";
    }

    protected function calculateTotalAmount(Collection $shifts)
    {
        return $shifts->sum('amount'); // or your logic
    }

    protected function extractHour($hourText)
    {
        preg_match('/(\d+)Hours/', $hourText, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function shift_summary(Request $request)
    {
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        // Generate all dates between start and end
        $allDates = collect();
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $allDates->push($date->toDateString());
        }

        // Fetch existing shifts from DB
        $shifts = RotaShift::query()
            ->when(
                !is_null($request->department_id),
                fn (Builder $builder) => $builder->where(function ($query) use ($request) {
                    $query->whereHas('employee', function (Builder $builder) use ($request) {
                        $builder->where('department_id', $request->department_id);
                    });
                })
            )
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('date');

        // Build final collection with all dates, even missing ones
        $summary = $allDates->map(function ($date) use ($shifts) {
            $shiftItems = $shifts[$date] ?? collect();

            return [
                'date' => $date,
                'total_hour' => $this->calculateTotalHour($shiftItems),
                'total_amount' => $this->calculateTotalAmount($shiftItems),
            ];
        });

        return response()->json([
            'data' => $summary,
            'meta' => [
                'total_hour' => $summary->sum(fn ($d) => $this->extractHour($d['total_hour'])), // optional
                'total_amount' => $summary->sum('total_amount'),
            ]
        ]);
        // $shifts = RotaShift::query()
        //         ->when(
        //             !is_null(request('department_id')),
        //             fn (Builder $builder) => $builder->where(function ($query) use($request){
        //                 $query->whereHas(
        //                     'employee', function (Builder $builder) use($request){
        //                         $builder
        //                             ->where('department_id', $request->department_id);
        //                     }
        //                 );
        //             })
        //         )
        //         ->whereBetween('date', [$request->start_date, $request->end_date])
        //         ->groupBy('date')
        //         ->get();
        // return ShiftSummaryResource::collection(
        //     $shifts
        // )->additional([
        //     'meta' => [
        //         'total_hour' => 123,
        //         'total_amount' => 34532
        //     ]
        // ]);
    }

    public function change_shift(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:rota_shifts,id',
            'date' => 'required|date|date_format:Y-m-d',
            'employee_id' => 'nullable|exists:users,id'
        ]);
        $update_shift = RotaShift::where('id', $request->shift_id)->update([
            'date' => $request->date,
            'employee_id' => $request->employee_id ? $request->employee_id : null
        ]);

        return apiResponse(
            data: null,
            message: "Shift Successfully Updated"
        );
    }

    public function store_shift(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date|date_format:Y-m-d',
            'start_time' => 'required',
            'end_time' => 'required',
            'published' => 'required',
            'sent_notification' => 'required',
        ]);

        $shift = RotaShift::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break' => $request->break ?? $request->break,
            'notes' => $request->notes ?? $request->notes,
            'published' => $request->published,
            'sent_notification' => $request->sent_notification
        ]);

        return apiResponse(
            data: $shift,
            message: 'Shift Stored Successfully'
        );
    }

    public function store_department_wise_shift(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'date' => 'required|date|date_format:Y-m-d',
            'start_time' => 'required',
            'end_time' => 'required',
            'published' => 'required',
            'sent_notification' => 'required',
        ]);

        if(isset($request->department_id))
        {
            $employees = User::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('status', User::USER_ACTIVE)
                ->where('department_id', $request->department_id)
                ->get();
            $remove_shift = RotaShift::query()
                        ->whereDate('date', $request->date)
                        ->whereHas(
                            'employee', function (Builder $builder) use($request){
                                $builder->where('company_id', auth()->user()->company_id)
                                        ->where('department_id', $request->department_id);
                            }
                        )
                        ->delete();
            foreach($employees as $employee)
            {
                $shift = RotaShift::create([
                    'employee_id' => $employee->id,
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'break' => $request->break ?? $request->break,
                    'notes' => $request->notes ?? $request->notes,
                    'published' => $request->published,
                    'sent_notification' => $request->sent_notification
                ]);
            }
        } else{
            $employees = User::query()
                ->where('company_id', auth()->user()->company_id)
                ->where('status', User::USER_ACTIVE)
                ->get();
            $remove_shift = RotaShift::query()
                        ->whereDate('date', $request->date)
                        ->whereHas(
                            'employee', function (Builder $builder) use($request){
                                $builder->where('company_id', auth()->user()->company_id);
                            }
                        )
                        ->delete();
            foreach($employees as $employee)
            {
                $shift = RotaShift::create([
                    'employee_id' => $employee->id,
                    'date' => $request->date,
                    'start_time' => $request->start_time,
                    'end_time' => $request->end_time,
                    'break' => $request->break ?? $request->break,
                    'notes' => $request->notes ?? $request->notes,
                    'published' => $request->published,
                    'sent_notification' => $request->sent_notification
                ]);
            }
        }

        return apiResponse(
            data: null,
            message: 'Shift Stored Successfully'
        );
    }

    public function edit_shift(RotaShift $rota_shift)
    {
        return apiResponse(
            data: $rota_shift
        );
    }

    public function update_shift(RotaShift $rota_shift, Request $request)
    {
        $request->validate([
            'employee_id' => 'required',
            'date' => 'required|date|date_format:Y-m-d',
            'start_time' => 'required',
            'end_time' => 'required',
            'published' => 'required',
            'sent_notification' => 'required',
            '_method' => 'required'
        ]);

        $update = $rota_shift->update([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'break' => $request->break ? $request->break : $rota_shift->break,
            'notes' => $request->notes ? $request->notes : $rota_shift->notes,
            'published' => $request->published,
            'sent_notification' => $request->sent_notification
        ]);

        return apiResponse(
            data: $update,
            message: 'Shift Updated Successfully'
        );
    }

    public function destroy_shift(RotaShift $rota_shift, Request $request)
    {
        $request->validate([
            '_method' => 'required',
        ]);

        $update = $rota_shift->delete();

        return apiResponse(
            data: null,
            message: 'Shift Deleted Successfully'
        );
    }
}
