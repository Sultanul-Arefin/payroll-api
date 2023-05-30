<?php

namespace Modules\SalaryItemsName\Http\Controllers;

use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\SalaryItemsName\Http\Requests\StoreSalaryItemsName;
use Modules\SalaryItemsName\Http\Resources\SalaryItemsNameResource;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

class SalaryItemsNameController extends Controller
{
    public function __construct(
        private SalaryItemsNameInterface $salaryItemsNameRepo
    ){
    }

    public function index($id)
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return SalaryItemsNameResource::collection(
            $this->salaryItemsNameRepo->allWithSearch(
                $id,
                ['*'],
                [],
                $rows
            )
        );
    }

    public function store(StoreSalaryItemsName $request)
    {
        try{
            $store = $this->salaryItemsNameRepo->create($request->toArray());
            return apiResponse([
                'data' => $store,
                'message' => 'Salary Items Stored Successfully',
                'status' => 'success',
                'statusCode' => 201
            ]);
        } catch(Exception $exception){
            Log::alert([
                'subject' => 'Store Salary Items Name',
                'message' => $exception->getMessage(),
                'overall' => $exception
            ]);
            return apiResponse([
                'data' => null,
                'message' => 'An error occured when trying to create salary item',
                'status' => 'error',
                'statusCode' => $exception->getCode()
            ]);
        }
    }
}
