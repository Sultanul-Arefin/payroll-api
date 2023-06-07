<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;
use Modules\ProjectManagement\Entities\ProjectAssociatedEmployee;
use Modules\ProjectManagement\Entities\ProjectColumn;
use Modules\ProjectManagement\Http\Requests\StoreProjectRequest;
use Modules\ProjectManagement\Http\Resources\ProjectResource;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectInterface;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectInterface $projectRepo
    ){
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
        $project = DB::transaction(function() use($request){
            $project = Project::create([
                'project_title' => $request->project_title,
                'project_description' => $request->project_description,
                'created_by' => auth()->user()->id,
                'company_id' => auth()->user()->company_id,
                'department_id' => 1
            ]);
            foreach(default_project_columns() as $key => $value){
                $column = ProjectColumn::create([
                    'column_name' => $value,
                    'company_id' => auth()->user()->company_id,
                    'created_by' => auth()->user()->id
                ]);
                ProjectAssociatedColumn::create([
                    'project_id' => $project->id,
                    'project_column_id' => $column->id,
                    'created_by' => auth()->user()->id
                ]);
            }
            foreach($request->assigned_employees as $employee_value){
                ProjectAssociatedEmployee::create([
                    'project_id' => $project->id,
                    'assigned_employees' => $employee_value
                ]);
            }
            return $project;
        });
        return apiResponse(
            data: $project,
            message: "Project Created Successfully",
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
