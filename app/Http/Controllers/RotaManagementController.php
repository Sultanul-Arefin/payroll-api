<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShiftResource;
use App\Http\Traits\RotaLocation;
use App\Http\Traits\RotaWorkSchedule;
use App\Models\RotaShift;
use App\Models\User;
use Illuminate\Http\Request;

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
            ->where('company_id', auth()->user()->company_id)
            ->where('status', User::USER_ACTIVE)
            ->get();
        return apiResponse(
            data: ShiftResource::collection(
                $user
            )
        );
    }

    public function store_shift(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required',
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
            message: 'Shift Stored Successfullys'
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
            'date' => 'required',
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
