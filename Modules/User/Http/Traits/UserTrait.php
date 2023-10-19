<?php

namespace Modules\User\Http\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

trait UserTrait
{
    public function user_info_for_payslip(Request $request)
    {
        $request->validate([
            'employee_id' => 'required'
        ]);

        $user = User::where('id', $request->employee_id)->select('name')->first();

        return apiResponse(
            data: $user,
            message: 'User Get Successfully!',
            status: 'success',
            statusCode: 200
        );
    }

    function activate_user(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|integer|in:1,2,3',
            'password' => 'required|string|max:30'
        ]);
        $update_user = User::where('id', $request->user_id)->update([
            'role_id' => $request->role_id,
            'status' => User::USER_ACTIVE,
            'password' => Hash::make($request->password)
        ]);
        return apiResponse(
            data: null,
            message: 'User Successfully Updated'
        );
    }
}