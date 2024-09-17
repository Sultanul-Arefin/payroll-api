<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Mail\ChangePassword;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Contracts\Activity;

class AuthController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only(['email', 'password']))) {
            return $this->apiResponse(
                data: [],
                message: 'Email & Password does not match with our record.',
                status: 'error',
                statusCode: 401
            );
        }
        $user = User::where('email', $request->email)->first();

        $package_info = DB::table('companies')
            ->join('company_associated_with_package', 'company_associated_with_package.company_id', 'companies.id')
            ->join('packages', 'packages.id', 'company_associated_with_package.package_id')
            ->select('packages.id as package_id', 'packages.package_name')
            ->where('companies.id', $user->company_id)
            ->first();
        // check if the user is deactivated
        if ($user->status == User::USER_DISABLE) {
            throw ValidationException::withMessages([
                'email' => ['Your account is suspended from the system'],
            ]);
        }

        // check if the user is deactivated
        if ($user->status == User::USER_PENDING) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.'],
            ]);
        }

        // check if the staff panel access has given
        if ($user->staff_interaction_panel_status == User::STAFF_INTERACTION_PANEL_NOT_GIVEN) {
            throw ValidationException::withMessages([
                'email' => ['You Don\'t Have Staff Interaction Panel Access! Please contact with your company.'],
            ]);
        }

        // activity log
        $std = new User();
        $activity = activity()
            ->causedBy($std)
            ->performedOn($std)
            ->tap(function (Activity $activity) use ($user) {
                $activity->causer_id = $user->id;
            })
            ->log('log in');

        // UPDATE LAST LOGIN TIME
        $user->update(['last_login' => now()]);

        return $this->apiResponse(
            data: [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'token' => $user->createToken($user->name)->plainTextToken,
                'user_info' => [
                    'user_name' => $user?->name,
                    'user_email' => $user?->email,
                    'user_image' => $user?->user_details?->user_image,
                    'user_role' => $user?->get_user_role?->name,
                    'line_manager' => $user?->assign_to_user?->name
                ],
                'company_info' => [
                    'company_id' => $user->company_id,
                    'company_name' => $user->company?->company_name,
                    'company_logo' => $user->company?->company_logo,
                    'no_of_working_days_per_week' => $user->company?->no_of_working_days_per_week,
                    'working_hours_per_day' => $user->company?->working_hours_per_day,
                ],
                'package_info' => [
                    'package_id' => auth()->user()->company?->associated_package?->package_id,
                    'package_name' => auth()->user()->company?->associated_package?->package?->package_name,
                ],
                'packageId' => auth()->user()->company?->associated_package?->package_id,
                'role' => auth()->user()->role_id,
            ],
            message: 'User logged in successful'
        );
    }

    public function reset_password(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            // 'password' => ['required', Rules\Password::defaults()]
        ]);
        // $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                    ? back()->with(['status' => __($status)])
                    : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Handle an incoming registration request.
     */
    public function change_password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'new_password' => ['required'],
        ]);
        if (\Hash::check($request->current_password, auth()->user()->password)) {
            if (! \Hash::check($request->new_password, auth()->user()->password)) {
                $user = User::where('id', auth()->user()->id)->update([
                    'password' => Hash::make($request->new_password),
                ]);
                $message = 'Password Updated successfully';
                Mail::to(auth()->user()->email)->queue(new ChangePassword($request->new_password, auth()->user()->name));

                return apiResponse(
                    data: [
                        'message' => $message,
                    ],
                    message: $message,
                    status: 'success',
                    statusCode: 200
                );
            } else {
                $message = 'Password Same as Before';

                return apiResponse(
                    data: [
                        'message' => $message,
                    ],
                    message: $message,
                    status: 'error',
                    statusCode: 422
                );
            }
        } else {
            $message = 'Current Password Doesn\'t match';

            return apiResponse(
                data: [
                    'message' => $message,
                ],
                message: $message,
                status: 'error',
                statusCode: 422
            );
        }
        //     if (Mail::failures()) {
        //         return response()->Fail('Sorry! Please try again latter');
        //    }else{
        //         return response()->success('Great! Successfully send in your mail');
        //       }
    }

    public function logout(Request $request)
    {
        // Auth::user()->tokens()->delete();
        auth()->user()->tokens()->delete();

        return apiResponse(null,
            message: 'Successfully logged out',
            status: 'success',
        );
    }
}
