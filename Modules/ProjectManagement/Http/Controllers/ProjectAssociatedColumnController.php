<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Entities\ProjectAssociatedColumn;

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

        $current_column_value = $current_column->column_position;

        // update
        $current_column->update([
            'column_position' => $target_column->column_position,
        ]);
        $target_column->update([
            'column_position' => $current_column_value,
        ]);

        return apiResponse(
            data: null,
            message: 'Column Position Updated Successfully',
            status: 'success'
        );
    }
}
