<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\StaffTask;
use App\Models\StaffTaskEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\StaffTaskResource;
use App\Http\Resources\StaffTaskCollection;
use App\Http\Traits\StaffTaskTrait;

class StaffTaskController extends Controller
{
    use StaffTaskTrait;
    
    // Display a listing of the resource
    public function index()
    {
        return new StaffTaskCollection(StaffTask::with(['createdBy', 'manager', 'employees'])->get());
    }

    // Store a newly created resource in storage
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $staffTask = StaffTask::create($request->all());

        // You can also attach employees here if provided
        if ($request->has('employees')) {
            $staffTask->employees()->attach($request->employees);
        }

        return new StaffTaskResource($staffTask);
    }

    // Display the specified resource
    public function show($id)
    {
        $staffTask = StaffTask::with(['createdBy', 'manager', 'employees'])->findOrFail($id);
        return new StaffTaskResource($staffTask);
    }

    // Update the specified resource in storage
    public function update(Request $request, $id)
    {
        $staffTask = StaffTask::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'created_by' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $staffTask->update($request->all());

        // Optionally, update employees
        if ($request->has('employees')) {
            $staffTask->employees()->sync($request->employees);
        }

        return new StaffTaskResource($staffTask);
    }

    // Remove the specified resource from storage
    public function destroy($id)
    {
        $staffTask = StaffTask::findOrFail($id);
        // $staffTask->employees()->detach();
        $staffTask->delete();

        return response()->json(['message' => 'Staff task deleted successfully']);
    }
}
