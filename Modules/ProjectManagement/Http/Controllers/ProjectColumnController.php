<?php

namespace Modules\ProjectManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProjectManagement\Entities\ProjectColumn;
use Modules\ProjectManagement\Http\Requests\StoreProjectColumnRequest;
use Modules\ProjectManagement\Http\Resources\ProjectColumnResource;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectColumnInterface;

class ProjectColumnController extends Controller
{
    public function __construct(
        private ProjectColumnInterface $projectColRepo
    ){
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return ProjectColumnResource::collection(
            $this->projectColRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }

    public function store(StoreProjectColumnRequest $request)
    {
        ProjectColumn::create([
            'column_name' => $request->column_name,
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->user()->id
        ]);
        return apiResponse(
            [],
            'Project Column Created Successfully',
            statusCode: 201
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
