<?php

namespace Modules\StaffObjective\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\StaffObjective\Http\Requests\StaffObjectiveRequest;
use Modules\StaffObjective\Models\StaffObjective;
use App\Http\Traits\Attachment;
use Modules\StaffObjective\Transformers\StaffObjectiveResource;

class StaffObjectiveController extends Controller
{
    use Attachment;
    /**
     * Display a listing of the resource.
     */
    public function index() : JsonResponse
    {
        $staffObjectives = StaffObjective::with('user', 'reviewBy')->get();
        $data = StaffObjectiveResource::collection($staffObjectives);
        return apiResponse($data, 'success', 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StaffObjectiveRequest $request)
    {
       // return $request->all();
        $fileName =  null;
        if($request->hasFile('review_document')){
            //call file upload trait and sent request file and directory path name
           $fileName = $this->uploadAttachment($request, 'review_document', 'staff_objectives');
        }
        try {
            StaffObjective::create([
                'user_id' => $request->user_id,
                'review_by' => $request->review_by,
                'review_date' => $request->review_date,
                'meeting_details' => $request->meeting_details,
                'review_notes' => $request->review_notes,
                'official_review_notes' => $request->official_review_notes,
                'review_document' => $fileName
            ]);
            return apiResponse(null, 'Successfully Data Stored 200', 200);
        }catch (\Exception $exception){
            return apiResponse(null, 'error', 403);
        }

    }

    /**
     * Show the specified resource.
     */
    public function show(StaffObjective $staffObjective) : JsonResponse
    {
       $data = new StaffObjectiveResource($staffObjective);
       return apiResponse($data, 'success', 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('staffobjective::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
