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
            'password' => 'required',
            'user_city' => 'required',
            'user_phone' => 'required',
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
            'user_image' => 'mimes:jpg,jpeg,png|max:2048',
            'contract_letter' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'national_id_card' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'cv' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'change_contact_letter' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'passport_file' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'visa' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'work_permit' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_1' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_2' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_3' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_4' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
            'other_docs_5' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
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
