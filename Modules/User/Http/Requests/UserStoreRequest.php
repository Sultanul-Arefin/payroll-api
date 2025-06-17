<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'email' => 'required|unique:users',
            'user_city' => 'required',
            'user_phone' => 'required',
            'country_id' => 'required|exists:countries,id',
            'employee_type' => 'required|integer|in:1,2,3,4',
            'payslip_type' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11',
            'designation_id' => 'required|integer|exists:designations,id',
            'department_id' => 'required|integer|exists:departments,id',
            'assign_to' => 'required|integer|exists:users,id',
            'wages' => 'nullable',
            'ordinary_time_rate' => 'required',
            'maternity_time_rate' => 'required',
            'paid_sick_leave_rate' => 'required',
            'unpaid_sick_leave_rate' => 'required',
            'holiday_rate' => 'required',
            'absent' => 'required',
            'bonus' => 'required',
            'overtime_rate' => 'required',
            'double_overtime_rate' => 'required',
            'recuperated_hour' => 'required',
            'user_image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'contract_letter' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'national_id_card' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'cv' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'change_contact_letter' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'passport_file' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'visa' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'work_permit' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_1' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_2' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_3' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_4' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_5' => 'nullable|mimes:jpg,jpeg,png,pdf,docx|max:2048',
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
