<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;
use Modules\Attendance\Http\Resources\AttendanceResourceForUser;
use Modules\Attendance\Repositories\Interfaces\AttendanceRepositoryInterface;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceRepositoryInterface $attendanceRepo){
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        // return TaskResource::collection(
            return AttendanceResourceForUser::collection(
                $this->attendanceRepo->allWithSearch(
                    ['*'],
                    [],
                    $rows
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $request->validate([
            'dates' => 'required',
            'in_time' => 'required',
            'out_time' => 'required'
        ]);

        $attendances = DB::transaction(function() use($request){
            $attendance = Attendance::create([
                'dates' => $request->dates,
                'user_id' => auth()->user()->id,
                'status' => Attendance::PENDING
            ]);
            $attendance_details = AttendanceDetail::create([
                'attendance_id' => $attendance->id,
                'in_time' => $request->in_time,
                'out_time' => $request->out_time
            ]);
            return $attendance;
        });

        return apiResponse(
            data: $attendances,
            message: 'Attendance Successfully Added',
            status: 'success'
        );
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('attendance::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('attendance::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
