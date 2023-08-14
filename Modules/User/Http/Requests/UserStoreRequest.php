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
            'recuperated_hour' => 'required'
            // 'user_image' => 'required|mimes:jpg,jpeg,png|max:2048',
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