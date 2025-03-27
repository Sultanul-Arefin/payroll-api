<?php

namespace Modules\ProjectManagement\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Entities\TaskFile;
use Modules\ProjectManagement\Entities\TaskAssociatedEmployee;
use Modules\ProjectManagement\Http\Requests\ChangeTaskColumnRequest;
use Modules\ProjectManagement\Http\Requests\FileUploadRequest;
use Modules\ProjectManagement\Http\Requests\StoreTaskRequest;
use Modules\ProjectManagement\Http\Requests\UpdateTaskRequest;
use Modules\ProjectManagement\Http\Resources\ProjectAssociatedResource;
use Modules\ProjectManagement\Http\Traits\CommentTrait;
use Modules\ProjectManagement\Http\Traits\TasksTrait;
use Modules\ProjectManagement\Notifications\ProjectManagementNotification;
use Modules\ProjectManagement\Repositories\Interfaces\TaskInterface;

class TaskController extends Controller
{
    use TasksTrait, CommentTrait;

    public function __construct(
        private TaskInterface $taskRepo
    ) {
    }

    public function index($id)
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        DB::enableQueryLog();
        $tasks = $this->taskRepo->allWithSearch(
            $id,
            ['*'],
            [],
            $rows
        );
        // return DB::getQueryLog();
        return ProjectAssociatedResource::collection(
            $tasks
        );
    }

    public function store(StoreTaskRequest $request)
    {
        $task = DB::transaction(function () use ($request) {
            $task = Task::create([
                'project_id' => $request->project_id,
                'project_associated_column_id' => $request->project_associated_column_id,
                'task_title' => $request->task_title,
                'created_by' => auth()->user()->id,
            ]);

            // TASK CREATION NOTIFICATION
            $project = Project::where('id', $request->project_id)->first();

            $data = [
                'title' => 'Task Created',
                'description' => "A New Task Named " . $request->task_title . " Has Been Created in Project: <b>{$project->project_title}</b>",
                'action' => [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone,
                ],
                'action_at' => date('Y-m-d H:i:s'),
                'type' => 'Project Management',
                'color' => '',
            ];
            $user = app(ProjectController::class)->getAdminUser();
            $user->notify(new ProjectManagementNotification($data));
            // $project->notify(new ProjectManagementNotification($data));
        });

        return apiResponse(
            data: $task,
            message: 'Task Created Successfully',
            status: 'success'
        );
    }

    public function uploadFile(FileUploadRequest $request, Task $task)
    {
        $allFile = [];

        // Start a database transaction
        $message = DB::transaction(function () use ($request, &$allFile, $task) {
            if ($request->hasFile('files')) {
                $file = $request->file('files');
                $uniqueFileName = rand(0, 999999999) . '_' . date('Ymdhis').'_' . rand(100, 999999999) . '.' . $file->getClientOriginalExtension();
                $storagePath = "tasks/{$task->id}";
                $file->storeAs($storagePath, $uniqueFileName, 'public');

                try {
                    $attach = TaskFile::create([
                        'task_id' => $task->id,
                        'files' => "{$storagePath}/{$uniqueFileName}",
                        'uploaded_by' => auth()->user()->id

                    ]);
                } catch (\Exception $ex) {
                    // $this->deleteAttachment($filename);
                    throw new CustomException($ex->getMessage(), 404);
                }
                return "{$storagePath}/{$uniqueFileName}";
            } else{
                return "Error In Task Upload";
            }
        });

        return apiResponse(
            data: [],
            message: "Successfully uploaded file",
            status: 'success'
        );
    }

    public function task_file(Task $task)
    {
        $data = $task?->taskFiles?->map(function($q){
            $data['id'] = $q->id;
            $data['task_id'] = $q->task_id;
            $data['url'] = env('APP_URL') . "/storage/" . $q->files;
            $data['uploaded_by'] = $q->uploaded_by;
            return $data;
        });
        return apiResponse(
            data: $data
        );
    }

    public function delete_file(Request $request, TaskFile $task_file)
    {
        $request->validate([
            '_method' => 'required'
        ]);
        $task_file->delete();
        return apiResponse(
            data: [],
            message: 'File Deleted Successfully'
        );
    }


    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('projectmanagement::show');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task_update = DB::transaction(function () use ($request, $task) {
            $task->update([
                'task_title' => $request->task_title,
                'task_description' => $request->task_description,
                'estimation_hour' => $request->estimation_hour,
                'start_date_time' => $request->start_date_time,
                'end_date_time' => $request->end_date_time,
            ]);

            /**
             * - Check If Assigned Employee Number Is Greater Than 0
             *      - If yes
             *          - then check if this task has any employees
             *              - If Yes
             *                  - Then Check If Assigned Employee Id & Already Assigned Employee Id Is Not Same
             *                      - If Same
             *                          - Do Nothing
             *                      - If Not Same
             *                          - Then Just Insert & Send Notification to the specific user
             *              - If No
             *                  - Then Just Insert & Send Notification to the specific user
             *      - If no
             *          - Do Nothing
             */
            if(count($request->assigned_employees) > 0){
                $task_associate_employees = TaskAssociatedEmployee::where('task_id', $task->id)->get();
                if(count($task_associate_employees) > 0){
                    foreach($request->assigned_employees as $assigned_employee_id){
                        foreach($task_associate_employees as $already_associated_employee){
                            if((int)$assigned_employee_id != (int)$already_associated_employee->user_id){
                                TaskAssociatedEmployee::create([
                                    'task_id' => $task->id,
                                    'user_id' => $assigned_employee_id,
                                ]);
                
                                // SEND NOTIFICATION TO CORRESPONDENT EMPLOYEE
                
                                $data = [
                                    'title' => 'Task Assigned!',
                                    'description' => 'Task Named ' . $task->task_title . ' Has Been Assigned to You',
                                    'action' => [
                                        'name' => auth()->user()->name,
                                        'email' => auth()->user()->email,
                                        'phone' => auth()->user()->phone,
                                    ],
                                    'action_at' => date('Y-m-d H:i:s'),
                                    'type' => 'Project Management',
                                    'color' => '',
                                ];
                                $user_whom_should_be_notified = User::where('id', $assigned_employee_id)->first();
                                $user_whom_should_be_notified->notify(new ProjectManagementNotification($data));
                            }
                        }
                    }
                } else{
                    foreach($request->assigned_employees as $assigned_employee_id){
                        TaskAssociatedEmployee::create([
                            'task_id' => $task->id,
                            'user_id' => $assigned_employee_id,
                        ]);
        
                        // SEND NOTIFICATION TO CORRESPONDENT EMPLOYEE
        
                        $data = [
                            'title' => 'Task Assigned!',
                            'description' => 'Task Named ' . $task->task_title . ' Has Been Assigned to You',
                            'action' => [
                                'name' => auth()->user()->name,
                                'email' => auth()->user()->email,
                                'phone' => auth()->user()->phone,
                            ],
                            'action_at' => date('Y-m-d H:i:s'),
                            'type' => 'Project Management',
                            'color' => '',
                        ];
                        $user_whom_should_be_notified = User::where('id', $assigned_employee_id)->first();
                        $user_whom_should_be_notified->notify(new ProjectManagementNotification($data));
                    }
                }
            }

            // TASK UPDATE NOTIFICATION
            $project = Project::where('id', $task->project_id)->first();

            $data = [
                'title' => 'Task Updated',
                'description' => 'Task Named ' . $task->task_title . ' Has Been Updated',
                'action' => [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone,
                ],
                'action_at' => date('Y-m-d H:i:s'),
                'type' => 'Project Management',
                'color' => '',
            ];
            $user = app(ProjectController::class)->getAdminUser();
            $user->notify(new ProjectManagementNotification($data));
            // $project->notify(new ProjectManagementNotification($data));

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
     *
     * @param  int  $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function change_task_column(ChangeTaskColumnRequest $request)
    {
        $update = $this->taskRepo->update($request->task_id, ['project_associated_column_id' => $request->updated_column_id]);

        // TASK COLUMN UPDATE NOTIFICATION
        $task = Task::where('id', $request->task_id)->first();
        // $project = Task::where('id', $request->task_id)->first()->project_associated_column->project;

        $data = [
            'title' => 'Task Column Position Updated',
            'description' => "Task Named <b>{$task->task_title}</b>'s Column Position Has Been Updated",
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => '',
        ];
        $user = app(ProjectController::class)->getAdminUser();
        $user->notify(new ProjectManagementNotification($data));

        return apiResponse(
            data: $update,
            message: 'Column Successfully Updated',
            status: 'success'
        );
    }
}
