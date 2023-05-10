<?php

namespace Modules\Company\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Company\Entities\Company;
use Modules\Company\Http\Requests\CompanyStoreRequest;
use Modules\Company\Http\Requests\CompanyUpdateRequest;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepo
    ){
    }

    public function index()
    {
        $company = $this->companyRepo->allWithSearch(
            ['*'],
            [],
        );
        if(!$company){
            return apiResponse(
                data: $company,
                message: 'Please Setup The Company!',
                status: 'error'
            );
        }
        return apiResponse(
            data: $company,
            message: 'Success',
            status: 'success'
        );
    }

    public function store(CompanyStoreRequest $request)
    {
        // check if already company associated
        if(auth()->user()->company_id != null){
            return apiResponse(
                data: null,
                message: 'Already Associated With a Company',
                status: 'error',
                statusCode: 403
            );
        }

        $store_company = DB::transaction(function() use(
            $request
        ){
            $company = $this->companyRepo->create([
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'company_email' => $request->company_email,
            ]);

            auth()->user()->update([
                'company_id' => $company->id
            ]);
            return $company;
        });

        return apiResponse(
            data: $store_company,
            message: 'Company Created Successfully',
            status: 'success',
            statusCode: 201
        );
    }

    public function update(CompanyUpdateRequest $request, Company $company)
    {
        $company->update([
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'company_email' => $request->company_email,
        ]);

        return apiResponse(
            data: $company,
            message: 'Company Updated Successfully',
            status: 'success',
            statusCode: 201
        );
    }
}
