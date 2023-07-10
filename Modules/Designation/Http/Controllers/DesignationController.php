<?php

namespace Modules\Designation\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Designation\Entities\Designation;
use Modules\Designation\Http\Requests\DesignationStoreRequest;
use Modules\Designation\Http\Requests\DesignationUpdateRequest;
use Modules\Designation\Repositories\Interfaces\DesignationInterface;

class DesignationController extends Controller
{
    public function __construct(private DesignationInterface $designation_repo)
    {

    }
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $designation = Designation::all();

        return apiResponse(
            data: $designation,
            message:  "Successfully",
            status: 'success'
        );
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('designation::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(DesignationStoreRequest $request)
    {
        try{
            $designation = $this->designation_repo->create([
                        'company_id' => auth()->user()->company_id,
                        'name' => $request->name,
                    ]);
        }catch(Exception $e){
            return apiResponse(
                data: null,
                message:  $e->getMessage(),
                status: 'error',
                statusCode:500
            );
        }


        return apiResponse(
            data: null,
            message:  "Successfully created designation",
            status: 'success'
        );
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Designation $designation)
    {
        return apiResponse(
            data: $designation,
            message:  "Successfully Get Designation",
            status: 'success'
        );

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('designation::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(DesignationUpdateRequest $request,Designation $designation)
    {
        try{
            $designation = $this->designation_repo->update($designation->id,[
                'name' => $request->name,
            ]);
        }catch(Exception $e){
            return apiResponse(
                data: null,
                message:  $e->getMessage(),
                status: 'error',
                statusCode:500
            );
        }

        return apiResponse(
            data: null,
            message:  "Successfully updated designation",
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
