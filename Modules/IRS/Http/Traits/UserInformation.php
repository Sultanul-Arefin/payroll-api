<?php
namespace Modules\IRS\Http\Traits;

use Illuminate\Http\Request;
use Modules\Company\Entities\Company;
use Modules\IRS\Http\Resources\GetSalaryResource;
use Modules\Payslip\Entities\Payslip;

trait UserInformation {
    public function getMainData(Request $request){
        $query = Company::query()
                ->where('id', auth()->user()->company_id)
                ->first();
        return $this->apiResponse(
            data: new GetSalaryResource($query)
        );
    }
}