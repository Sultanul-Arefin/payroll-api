<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Http\Requests\StoreProjectRequest;
use Modules\ProjectManagement\Http\Resources\ProjectOverviewResource;
use Modules\ProjectManagement\Http\Resources\ProjectResource;
use Modules\ProjectManagement\Notifications\ProjectManagementNotification;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectInterface;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectInterface $projectRepo
    ) {
    }

    function project_overview(Project $project) {
        $data = Project::query()
                ->when(!is_null(request('status')), function ($query) {
                    $query->where(
                        'is_completed',
                        request('status')
                    );
                })
                ->where('company_id', auth()->user()->company_id)
                ->with([])
                ->where('id', $project->id)
                ->first();
        return new ProjectOverviewResource($data);
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return ProjectResource::collection(
            $this->projectRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }

    public function store(StoreProjectRequest $request)
    {
        $project = DB::transaction(function () use ($request) {
            $project = Project::create([
                'project_title' => $request->project_title,
                'project_description' => $request->project_description,
                'created_by' => auth()->user()->id,
                'company_id' => auth()->user()->company_id,
                'department_id' => 1,
            ]);
            // PROJECT CREATION NOTIFICATION
            $data = [
                'title' => 'Project Created',
                'description' => 'A New Project Has Been Created',
                'action' => [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                    'phone' => auth()->user()->phone,
                ],
                'action_at' => date('Y-m-d H:i:s'),
                'type' => 'Project Management',
                'color' => '',
            ];
            $project->notify(new ProjectManagementNotification($data));

            $index = 0;
            foreach(default_project_columns() as $key => $value){
                ProjectAssociatedColumn::create([
                    'project_id' => $project->id,
                    'project_column_name' => $value,
                    'created_by' => auth()->user()->id,
                    'column_position' => ++$index
                ]);
                // COLUMN CREATION NOTIFICATION
                $data = [
                    'title' => 'Column Created',
                    'description' => 'Column ' . $value . ' Has Been Created',
                    'action' => [
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                    ],
                    'action_at' => date('Y-m-d H:i:s'),
                    'type' => 'Project Management',
                    'color' => ''
                ];
                $project->notify(new ProjectManagementNotification($data));
            }
            return $project;
        });

        return apiResponse(
            data: $project,
            message: 'Project Created Successfully',
            status: 'success'
        );
    }

    function update_status(Project $project, Request $request) {
        $request->validate([
            'status' => 'required|integer|in:0,1,2'
        ]);
        $project->update([
            'is_completed' => $request->status
        ]);
        // UPDATE PROJECT STATUS NOTIFICATION
        $data = [
            'title' => 'Project Status Updated',
            'description' => 'Project Status Updated to ' . $request->status,
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => ''
        ];
        $project->notify(new ProjectManagementNotification($data));
        return apiResponse(
            data: $project,
            message: 'Project Status Updated Successfully',
            status: 'success'
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

    public function update(Project $project, Request $request)
    {
        $request->validate([
            '_method' => 'required',
            'project_title' => 'required',
            'project_description' => 'required',
            'project_manager' => 'required|exists:users,id'
        ]);
        $project->update([
            'project_title' => $request->project_title,
            'project_description' => $request->project_description,
            'project_manager' => $request->project_manager
        ]);
        return apiResponse(
            data: $project
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
}
