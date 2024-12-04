<?php

namespace Modules\SalaryItemsName\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;
use Modules\SalaryItemsName\Http\Requests\StoreSalaryItemsName;
use Modules\SalaryItemsName\Http\Resources\SalaryItemsNameResource;
use Modules\SalaryItemsName\Http\Services\SalaryLeaveItemsService;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

class SalaryItemsNameController extends Controller
{
    public function __construct(
        private SalaryItemsNameInterface $salaryItemsNameRepo,
        private SalaryLeaveItemsService $leaveSalaryItems
    ) {
    }

    public function index(SalaryItemsCategory $salary_item_category, Request $request)
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        if ($salary_item_category->id == 5) {
            $request->validate([
                'tax_type' => 'required|in:straight,threshold',
            ]);
        }

        return SalaryItemsNameResource::collection(
            $this->salaryItemsNameRepo->allWithSearch(
                $salary_item_category,
                ['*'],
                [],
                $rows
            )
        );
    }

    public function store(StoreSalaryItemsName $request)
    {
        try {
            $request->merge([
                'company_id' => auth()->user()->company_id,
            ]);
            $store = $this->salaryItemsNameRepo->create($request->toArray());
            if (isset($request->leave_releated_items) && $request->leave_releated_items == 1) {
                $leaveSalaryItems = $this->leaveSalaryItems->create($store);
            }

            if (isset($request->tax_type) && $request->tax_type == "threshold") {
                $update_item = $store->update([
                    'is_threshold' => 2
                ]);
            }

            return apiResponse(
                data: $store,
                message: 'Salary Items Stored Successfully',
                status: 'success',
                statusCode: 201
            );
        } catch (Exception $exception) {
            Log::alert([
                'subject' => 'Store Salary Items Name',
                'message' => $exception->getMessage(),
                'overall' => $exception,
            ]);

            return apiResponse([
                'data' => null,
                'message' => 'An error occured when trying to create salary item',
                'status' => 'error',
                'statusCode' => $exception->getCode(),
            ]);
        }
    }
}
