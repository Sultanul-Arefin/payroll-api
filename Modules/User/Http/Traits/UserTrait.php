<?php

namespace Modules\User\Http\Traits;

use App\Models\User;
use Illuminate\Http\Request;

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
}