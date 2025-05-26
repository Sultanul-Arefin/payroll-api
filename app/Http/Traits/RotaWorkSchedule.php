<?php

namespace App\Http\Traits;

use App\Models\RotaWorkSchedule as ModelsRotaWorkSchedule;
use App\Models\RotaWorkScheduleCompany;
use Illuminate\Http\Request;

trait RotaWorkSchedule
{
    public function employee_schedule($employee_id)
    {
        $schedule = ModelsRotaWorkSchedule::where('employee_id', $employee_id)->firstOrFail();

        return apiResponse(
            data: $schedule
        );
    }

    public function update_employee_schedule($employee_id, Request $request)
    {
        $request->validate([
            "saturday_off"  => "required|in:0,1",
            "sunday_off"    => "required|in:0,1",
            "monday_off"    => "required|in:0,1",
            "tuesday_off"   => "required|in:0,1",
            "wednesday_off" => "required|in:0,1",
            "thursday_off"  => "required|in:0,1",
            "friday_off"    => "required|in:0,1",
            "_method"       => "required"
        ]);
        $data = $request->only([
            'employee_id', 'saturday_off', 'sunday_off', 'monday_off', 'tuesday_off', 'wednesday_off', 'thursday_off', 'friday_off',
        ]);
        $data = array_map(fn($val) => (bool) $val, $data);
        
        $schedule = ModelsRotaWorkSchedule::where('employee_id', $employee_id)->firstOrFail();

        $schedule->update([
            "saturday_off" => $data["saturday_off"],
            "sunday_off" => $data["sunday_off"],
            "monday_off" => $data["monday_off"],
            "tuesday_off" => $data["tuesday_off"],
            "wednesday_off" => $data["wednesday_off"],
            "thursday_off" => $data["thursday_off"],
            "friday_off" => $data["friday_off"],
        ]);

        return apiResponse(
            data: $schedule
        );
    }

    public function company_schedule($company_id)
    {
        $schedule = RotaWorkScheduleCompany::where('company_id', $company_id)->firstOrFail();

        return apiResponse(
            data: $schedule,
            message: "Employee Schedule Updated Successfully"
        );
    }

    public function update_company_schedule($company_id, Request $request)
    {
        $request->validate([
            "saturday_off"  => "required|in:0,1",
            "sunday_off"    => "required|in:0,1",
            "monday_off"    => "required|in:0,1",
            "tuesday_off"   => "required|in:0,1",
            "wednesday_off" => "required|in:0,1",
            "thursday_off"  => "required|in:0,1",
            "friday_off"    => "required|in:0,1",
            "_method"       => "required"
        ]);
        $data = $request->only([
            'employee_id', 'saturday_off', 'sunday_off', 'monday_off', 'tuesday_off', 'wednesday_off', 'thursday_off', 'friday_off',
        ]);
        $data = array_map(fn($val) => (bool) $val, $data);
        
        $schedule = RotaWorkScheduleCompany::where('company_id', $company_id)->firstOrFail();

        $schedule->update([
            "saturday_off" => $data["saturday_off"],
            "sunday_off" => $data["sunday_off"],
            "monday_off" => $data["monday_off"],
            "tuesday_off" => $data["tuesday_off"],
            "wednesday_off" => $data["wednesday_off"],
            "thursday_off" => $data["thursday_off"],
            "friday_off" => $data["friday_off"],
        ]);

        return apiResponse(
            data: $schedule,
            message: "Company Schedule Updated Successfully"
        );
    }
}