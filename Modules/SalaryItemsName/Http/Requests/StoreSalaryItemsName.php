<?php

namespace Modules\SalaryItemsName\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryItemsName extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'salary_items_category_id' => 'required',
            'name' => 'required'
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