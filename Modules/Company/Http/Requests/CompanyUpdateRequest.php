<?php

namespace Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_name' => 'required',
            'company_email' => 'required',
            'company_address' => 'required',
            'company_phone' => 'required',
            // 'company_logo' => 'mimes:jpg,jpeg,png|max:2048',
            'company_registration_no' => 'nullable',
            'government_employee_no' => 'nullable',
            'fiscal_year_from' => 'required|date|date_format:Y-m-d',
            'fiscal_year_to' => 'required|date|date_format:Y-m-d',
            'bank_name' => 'nullable',
            'bank_bic_or_swift_code' => 'nullable',
            'bank_iban_or_account_no' => 'nullable',
            'contact_person_name' => 'nullable',
            'contact_person_email' => 'nullable',
            'contact_person_phone' => 'nullable',
            'employer_identification_number' => 'nullable|string|max:20',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
