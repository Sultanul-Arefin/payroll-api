<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\Task;
use Modules\ProjectManagement\Notifications\ProjectManagementNotification;

class ProjectAssociatedColumnController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        return view('projectmanagement::index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        return view('projectmanagement::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        $column_value = ProjectAssociatedColumn::where('project_id', $request->project_id)->get();
        $max_column_value = $column_value->max('column_position') ?? 0;

        $column = ProjectAssociatedColumn::create([
            'project_id' => $request->project_id,
            'project_column_name' => $request->project_column_name,
            'created_by' => auth()->user()->id,
            'column_position' => ++$max_column_value,
        ]);

        $project = Project::where('id', $request->project_id)->first();
        // CREATE NEW PROJECT COLUMN NOTIFICATION
        $data = [
            'title' => 'Project Column Created',
            'description' => "Project Column Named " . $request->project_column_name . " Has Created in Project: <b>{$project->project_title}</b>",
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => ''
        ];
        $user = app(ProjectController::class)->getAdminUser();
        $user->notify(new ProjectManagementNotification($data));
        // $project->notify(new ProjectManagementNotification($data));

        return apiResponse(
            data: $column,
            message: 'Project Column Created Successfully',
            status: 'success'
        );
    }

    public function change_column_index(Request $request)
    {
        $request->validate([
            'current_column_id' => 'required|integer',
            'target_column_id' => 'required|integer',
        ]);
        $current_column = ProjectAssociatedColumn::where('id', $request->current_column_id)->first();
        $target_column = ProjectAssociatedColumn::where('id', $request->target_column_id)->first();

        // storing the current column value to update target column
        $current_column_value = $current_column->column_position;

        // update
        $current_column->update([
            'column_position' => $target_column->column_position,
        ]);
        $target_column->update([
            'column_position' => $current_column_value,
        ]);

        // PROJECT COLUMN INDEX UPDATED NOTIFICATION
        $project = Project::where('id', $current_column->project_id)->first();;
        $data = [
            'title' => 'Project Column Position Updated',
            'description' => 'Project Column Updated to ' . $request->target_column_id . ' from ' . $request->current_column_id,
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => ''
        ];
        $user = app(ProjectController::class)->getAdminUser();
        $user->notify(new ProjectManagementNotification($data));

        return apiResponse(
            data: null,
            message: 'Column Position Updated Successfully',
            status: 'success'
        );
    }

    function update_project_associated_column(ProjectAssociatedColumn $project_associated_column, Request $request) {
        $request->validate([
            'project_column_name' => 'required'
        ]);
        $old_name = $project_associated_column->project_column_name;
        $project_associated_column->update([
            'project_column_name' => $request->project_column_name
        ]);

        // UPDATE PROJECT COLUMN NAME NOTIFICATION
        $project = Project::where('id', $project_associated_column->project_id)->first();
        $data = [
            'title' => 'Project Column Updated',
            'description' => 'Project Column Name Updated to ' . $request->project_column_name . 'from ' . $old_name,
            'action' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'action_at' => date('Y-m-d H:i:s'),
            'type' => 'Project Management',
            'color' => ''
        ];
        $user = app(ProjectController::class)->getAdminUser();
        $user->notify(new ProjectManagementNotification($data));
        return apiResponse(
            data: $project_associated_column,
            message: 'Column Name Updated Successfully'
        );
    }

    public function destroy(ProjectAssociatedColumn $column)
        { 
            
            // Delete the column
            $column->delete();

            return apiResponse(
                data: $column,
                message: 'Project Column Delete Successfully',
                status: 'success'
            );
        }
}
