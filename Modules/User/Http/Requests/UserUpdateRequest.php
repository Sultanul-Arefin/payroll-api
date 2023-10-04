<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable',
            'designation_id' => 'nullable',
            'assign_to' => 'nullable',
            'department_id' => 'nullable',
            'zip_code' => 'required',
            'gender' => 'required',
            'payment_type' => 'required',
            'user_city' => 'required',
            'user_phone' => 'required',
            'user_image' => 'mimes:jpg,jpeg,png|max:2048',
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
