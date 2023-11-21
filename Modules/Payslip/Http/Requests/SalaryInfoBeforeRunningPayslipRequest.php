<?php

namespace Modules\Payslip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalaryInfoBeforeRunningPayslipRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'employee_id|exists:users,id',
            'month' => 'required',
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
