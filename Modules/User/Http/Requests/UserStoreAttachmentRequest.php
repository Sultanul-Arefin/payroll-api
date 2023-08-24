<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreAttachmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
             'contract_letter' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
             'national_id_card' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
             'cv' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
             'change_contact_letter' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
             'passport' => 'mimes:jpg,jpeg,png,pdf,docx|max:2048',
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
