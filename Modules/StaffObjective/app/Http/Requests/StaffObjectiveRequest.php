<?php

namespace Modules\StaffObjective\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffObjectiveRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required',
            'review_by' => 'required',
            'review_date' => 'required|date',
            'review_document' => 'mimes:pdf,docx|max:2048'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
