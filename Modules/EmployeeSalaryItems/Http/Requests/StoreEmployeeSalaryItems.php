<?php

namespace Modules\EmployeeSalaryItems\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeSalaryItems extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'salary_item_id' => 'required|exists:salary_items_names,id',
            'is_percentage' => 'required|integer|in:1,0',
            'is_general' => 'required|integer|in:1,0',
            'employee_id' => 'nullable|required_if:is_general,0'
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