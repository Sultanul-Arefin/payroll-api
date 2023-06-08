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
     * @return Renderable
     */
    public function index()
    {
        return view('projectmanagement::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('projectmanagement::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $column = ProjectAssociatedColumn::create([
            'project_id' => $request->project_id,
            'project_column_name' => $request->project_column_name,
            'created_by' => auth()->user()->id
        ]);

        return apiResponse(
            data: $column,
            message: "Project Column Created Successfully",
            status: 'success'
        );
    }
}
