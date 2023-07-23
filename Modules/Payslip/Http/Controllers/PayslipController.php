<?php

namespace Modules\Payslip\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;
use Modules\Payslip\Http\Resources\CategoryResource;
use Modules\Payslip\Http\Resources\PayslipResource;
use Modules\Payslip\Http\Resources\SalaryItemsCategoryResource;
use Modules\Payslip\Http\Services\PayslipService;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\SalaryItemsCategory\Entities\SalaryItemsCategory;

class PayslipController extends Controller
{
    function __construct(
        public PayslipService $payslipService,
        public PayslipRepositoryInterface $payslipRepositoryInterface
    ) {
        
    }

    function employee_salary_items(Request $request) {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $user = User::where('id', request('employee_id'))->first();

        return apiResponse(
            data: [
                'all_items' => $user->salary_items?->map(function($s_items){
                    return [
                        // 'id' => $s_items->id,
                        'name' => $s_items->salaryItemsName?->name,
                        'value' => $s_items->amount
                    ];
                }),
            ],
            message: 'success',
            statusCode: 200
        );
    }

    function employee_salary_items_calculation(Request $request) {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $user = User::where('id', request('employee_id'))->first();
        $salary_category = SalaryItemsCategory::query()
                            ->whereHas(
                                'salaryItemsName', fn(Builder $query) => $query
                                    ->whereHas(
                                        'employeeSalaryItem', fn(Builder $query) => $query
                                            ->where('company_id', auth()->user()->company_id)
                                            ->where('employee_id', $user->id)
                                    )
                            )
                            ->get();
        return CategoryResource::collection(
            $salary_category
        );
        return $salary_category;
    }
    public function salary_information_before_running_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required'
        ]);
        $user = User::where('id', request('employee_id'))->first();

        // category wise value
        $category_wise_value = SalaryItemsCategory::query()
                    ->whereHas(
                        'salaryItemsName', fn(Builder $query) => $query
                            ->whereHas(
                                'employeeSalaryItem', fn(Builder $query) => $query
                                    ->where('company_id', auth()->user()->company_id)
                                    ->where('employee_id', $user->id)
                            )
                    )
                    ->with(
                        'salaryItemsName.employeeSalaryItem'
                    )
                    ->get();
        return $category_wise_value;
        foreach($category_wise_value as $cwv){

        }
        

        return apiResponse(
            data: [
                // 'all_items' => $user->salary_items?->map(function($s_items){
                //     return [
                //         // 'id' => $s_items->id,
                //         'name' => $s_items->salaryItemsName?->name,
                //         'value' => $s_items->amount
                //     ];
                // }),
                'category_wise_value' => SalaryItemsCategory::query()
                                    ->whereHas(
                                        'salaryItemsName', fn(Builder $query) => $query
                                            ->whereHas(
                                                'employeeSalaryItem', fn(Builder $query) => $query
                                                    ->where('company_id', auth()->user()->company_id)
                                                    ->where('employee_id', $user->id)
                                            )
                                    )
                                    ->with(
                                        'salaryItemsName.employeeSalaryItem'
                                    )
                                    // ->with(
                                    //     'salaryItemsName', function($q){
                                    //         $q->withSum('employeeSalaryItem', 'amount');
                                    //     }
                                    // )
                                    ->get()
                // 'category_wise_value' => $user->salary_items?->map(function($items){
                //     return [
                //         'name' => $items?->salaryItemsName?->salaryItemsCategory?->name,
                //         'value' => $items?->salaryItemsName?->salaryItemsCategory?->salaryItemsName?->map(function($new_items){
                //             $new_items->load('employeeSalaryItem');
                //             return $new_items->employeeSalaryItem->sum('amount');
                //         })
                //     ];
                // })
                // 'category_wise_value' => $user->salary_items?->salaryItemsName?->salaryItemsCategory
            ],
            message: 'success',
            statusCode: 200
        );
        // $rows = 15;
        // if(request()?->has('rows')){
        //     $rows = (int) request('rows');
        // }
        // return SalaryItemsCategoryResource::collection(
        //     $this->payslipRepositoryInterface->getSalaryItemsCategory(
        //         ['*'],
        //         [],
        //         $rows
        //     )
        // );
    }

    function run_payslip(Request $request) {
        $request->validate([
            'employee_id' => 'required',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'payment_date' => 'required|date_format:Y-m-d'
        ]);
    }

    function preview_payslip() {
        
    }

    function payslips() {

        $rows = 15;
        if(request()?->has('rows')){
            $rows = (int) request('rows');
        }

        return PayslipResource::collection(
            $this->payslipRepositoryInterface->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }
}
