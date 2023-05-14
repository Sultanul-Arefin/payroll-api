<?php

namespace Modules\Company\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Company\Entities\Company;
use Modules\Company\Http\Requests\CompanyStoreRequest;
use Modules\Company\Http\Requests\CompanyUpdateRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Company\Http\Traits\CompanyTrait;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;

class CompanyController extends Controller
{
    use CompanyTrait;
    public function __construct(private CompanyRepositoryInterface $companyRepo){
    }

    public function index()
    {
        $company = $this->companyRepo->allWithSearch(
            ['*'],
            [],
        );
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
                'company_phone' => $request->company_phone,
                'company_logo' => $this->upload_logo($request),
                'company_website' => $request->company_website,
                'company_registration_no' => $request->company_registration_no,
                'government_employee_no' => $request->government_employee_no,
                'fiscal_year_from' => $request->fiscal_year_from,
                'fiscal_year_to' => $request->fiscal_year_to,
                'bank_name' => $request->bank_name,
                'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code,
                'bank_iban_or_account_no' => $request->bank_iban_or_account_no,
                'contact_person_name' => $request->contact_person_name,
                'contact_person_email' => $request->contact_person_email,
                'contact_person_phone' => $request->contact_person_phone,
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
        $company_update = DB::transaction(function() use(
            $request,$company
        ){
            $updated_logo = $company->company_logo;

            if($request->file('company_logo')){
                // unlink goes here
                if($this->isLogoExist($company->company_logo)){
                    $this->deleteLogo($company->company_logo);
                }
                $updated_logo = $this->upload_logo($request);
            }

            $company = $this->companyRepo->update($company->id,[
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'company_email' => $request->company_email,
                'company_phone' => $request->company_phone,
                'company_logo' => $updated_logo,
                'company_website' => $request->company_website,
                'status' => $company->status == Company::PENDING ? Company::ACTIVE : $company->status,
                'company_registration_no' => $request->company_registration_no,
                'government_employee_no' => $request->government_employee_no,
                'fiscal_year_from' => $request->fiscal_year_from,
                'fiscal_year_to' => $request->fiscal_year_to,
                'bank_name' => $request->bank_name,
                'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code,
                'bank_iban_or_account_no' => $request->bank_iban_or_account_no,
                'contact_person_name' => $request->contact_person_name,
                'contact_person_email' => $request->contact_person_email,
                'contact_person_phone' => $request->contact_person_phone,
            ]);

            return $company;
        });
      

        return apiResponse(
            data: $company_update,
            message: $company_update ? 'Company Updated Successfully':'Company Updated Failed',
            status: 'success',
            statusCode: 201
        );
    }
}