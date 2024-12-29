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
            'name' => 'required',
            'tax_type' => 'required_if:salary_items_category_id,5|in:straight,threshold',
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
