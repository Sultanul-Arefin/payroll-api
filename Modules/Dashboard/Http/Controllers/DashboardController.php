<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Models\User;
use http\Env\Response;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeaveManagement\Http\Resources\EmployeeResource;

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

       $data =  User::where('company_id', auth()->user()->id)
           ->where('status', 1)
           ->selectRaw('COUNT(*) as count')
           ->first();
        return response()->json(['data'=>$data]);
    }
}
