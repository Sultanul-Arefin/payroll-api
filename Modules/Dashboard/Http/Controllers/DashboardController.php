<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Models\User;
use http\Env\Response;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Company\Entities\AnnualHoliday;
use Modules\Department\Entities\Department;
use Modules\Dashboard\Http\Resources\HolidayResource;


class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('dashboard::index');
    }

    /**
     * all employees count under a company
     * @return JsonResponse
     */
    public function totalEmployee() : JsonResponse
    {

       $data =  User::where('company_id', auth()->user()->company_id)
           ->where('status', 1)
           ->selectRaw('COUNT(*) as count')
           ->first();
        return response()->json(['data'=>$data]);
    }

    /**
     * all department count under a company
     * @return JsonResponse
     */
    public function totalDepartment() : JsonResponse
    {
        $data = Department::where('company_id', auth()->user()->company_id)
            ->selectRaw('COUNT(*) as count')
            ->first();
        return response()->json(['data'=>$data]);
    }
    public function holiday()
    {
       $holidays = AnnualHoliday::where('company_id', auth()->user()->company_id)->get();
       return HolidayResource::collection($holidays);
    }
}
