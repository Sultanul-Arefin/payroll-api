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
use Modules\Company\Entities\Company;
use Modules\User\Entities\UserDetails;
use Spatie\Activitylog\Contracts\Activity;

class AuthController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function store(LoginRequest $request): JsonResponse
    {
        if(!Auth::attempt($request->only(['email', 'password']))){
            return $this->apiResponse(
                data: [],
                message: 'Email & Password does not match with our record.',
                status: 'error',
                statusCode: 401
            );
        }
        $user = User::where('email', $request->email)->first();
        $user_details = DB::table('companies')
                            ->join('users','users.company_id','companies.id')
                            ->join('user_details','users.id','user_details.user_id')
                            ->join('roles','roles.id','users.user_role')
                            ->select('user_details.user_image','companies.company_name','companies.company_logo','roles.name as role_name')
                            ->where('users.id',$user->id)
                            ->first();

        $package_info = DB::table('companies')
                            ->join('company_associated_with_package','company_associated_with_package.company_id','companies.id')
                            ->join('packages','packages.id','company_associated_with_package.package_id')
                            ->select('packages.id as package_id','packages.package_name')
                            ->where('companies.id',$user->company_id)
                            ->first();
        // check if the user is deactivated
        if ($user->status == User::USER_DISABLE) {
            throw ValidationException::withMessages([
                'email' => ['Your account is suspended from the system']
            ]);
        }

        // check if the user is deactivated
        if ($user->status == User::USER_PENDING) {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active.']
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
                // 'title' => $user->title,
                // 'first_name' => $user->first_name,
                // 'last_name' => $user->last_name,
                'name' => $user->name,
                'token' => $user->createToken($user->name)->plainTextToken,
                'user_info' => [
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_image' => $user_details->user_image,
                    'user_role' => $user_details->role_name,
                ],
                'company_info' => [
                    'company_id' => $user->company_id,
                    'company_name' => $user_details->company_name,
                    'company_logo' => $user_details->company_logo,
                ],
                'package_info' => [
                    'package_id' => $package_info->package_id,
                    'package_name' => $package_info->package_name
                ]
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
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function change_password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'new_password' => ['required']
        ]);
        if(\Hash::check($request->current_password, auth()->user()->password)){
            if (!\Hash::check($request->new_password, auth()->user()->password)) {
                $user = User::where('id', auth()->user()->id)->update([
                    'password' => Hash::make($request->new_password)
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
        } else{
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
    
    public function logout(Request $request){
        Auth::user()->tokens()->delete();

        return apiResponse(null,
            message: 'Successfully logged out',
            status: 'success',
        );
    }
}