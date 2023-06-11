<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;
use Modules\ProjectManagement\Http\Requests\StoreTaskRequest;
use Modules\ProjectManagement\Http\Requests\UpdateTaskRequest;
use Modules\ProjectManagement\Http\Resources\ProjectAssociatedResource;
use Modules\ProjectManagement\Http\Resources\TaskResource;
use Modules\ProjectManagement\Http\Traits\TasksTrait;
use Modules\ProjectManagement\Repositories\Interfaces\TaskInterface;

class TaskController extends Controller
{
    use TasksTrait;

    public function __construct(
        private TaskInterface $taskRepo
    ){
    }

    public function index($id)
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        // return TaskResource::collection(
        return ProjectAssociatedResource::collection(
            $this->taskRepo->allWithSearch(
                $id,
                ['*'],
                [],
                $rows
            )
        );
    }
    
    public function store(StoreTaskRequest $request)
    {
        $task = DB::transaction(function() use($request){
            $task = Task::create([
                'project_id' => $request->project_id,
                'project_associated_column_id' => $request->project_associated_column_id,
                'task_title' => $request->task_title,
                'task_description' => $request->task_description,
                'created_by' => auth()->user()->id,
            ]);
        });
        return apiResponse(
            data: $task,
            message: "Task Created Successfully",
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
        return view('projectmanagement::show');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task_update = DB::transaction(function() use($request, $task){
            $task->update([
                'task_title' => $request->task_title,
                'task_description' => $request->task_description,
                'estimation_hour' => $request->estimation_hour,
                'start_date_time' => $request->start_date_time,
                'end_date_time' => $request->end_date_time,
            ]);
            foreach($request->assigned_employees as $employee_value){
                TaskAssociatedEmployee::create([
                    'task_id' => $task->id,
                    'user_id' => $employee_value
                ]);
            }
            return $task;
        });
        return apiResponse(
            data: $task,
            message: 'Task Updated Successfully',
            status: 'success'
        );
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
