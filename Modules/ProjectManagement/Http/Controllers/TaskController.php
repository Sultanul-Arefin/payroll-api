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
        return apiResponse(
            data: $task?->taskFiles
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
            // delete associate employees
            TaskAssociatedEmployee::where('task_id', $task->id)->delete();
            foreach ($request->assigned_employees as $employee_id) {
                TaskAssociatedEmployee::create([
                    'task_id' => $task->id,
                    'user_id' => $employee_id,
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
                $user_whom_should_be_notified = User::where('id', $employee_id)->first();
                $user_whom_should_be_notified->notify(new ProjectManagementNotification($data));
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
