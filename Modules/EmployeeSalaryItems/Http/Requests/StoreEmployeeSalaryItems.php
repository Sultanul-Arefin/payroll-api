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
            'is_percentage' => 'required',
            'is_general' => 'required',
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