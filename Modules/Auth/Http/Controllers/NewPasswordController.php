<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Handle an incoming new password request.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', Rules\Password::defaults()]
        ]);

        // if ($password != $new_confirm_password) {
        //     return response()->json(
        //         [
        //             'status' => 'error',
        //             'message' => 'Form Validation failed',
        //             'data' => [
        //                 'errors' => [
        //                     'password' => [
        //                         "The password confirmation doesn't match"
        //                     ]
        //                 ]
        //             ]
        //         ],
        //         422
        //     );
        // }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $status = Password::reset(
            $request->only(
                'password',
                'password_confirmation',
                'token',
                'email'
            ),
            // static function ($user) use ($request, $password) {
                static function ($user) use ($request) {
                $user
                    ->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60)
                    ])
                    ->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? $this->apiResponse([], 'Your password has been reset!')
            : throw ValidationException::withMessages([
                'email' => [__($status)]
            ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    // public function update_password(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'current_password' => ['required', 'string', 'max:255'],
    //         'password' => ['required', Rules\Password::defaults()]
    //     ]);

    //     $key = env('APP_PASS_PHRASE'); // random key generated for checking with frontend & backend

    //     $current_password = $this->decrypt($request->current_password, $key);
    //     $new_password = $this->decrypt($request->password, $key);
    //     $new_confirm_password = $this->decrypt(
    //         $request->password_confirmation,
    //         $key
    //     );

    //     if ($new_password != $new_confirm_password) {
    //         return response()->json(
    //             [
    //                 'status' => 'error',
    //                 'message' => 'Form Validation failed',
    //                 'data' => [
    //                     'errors' => [
    //                         'password' => [
    //                             "The password confirmation doesn't match"
    //                         ]
    //                     ]
    //                 ]
    //             ],
    //             422
    //         );
    //     }

    //     if (\Hash::check($current_password, auth()->user()->password)) {
    //         if (!\Hash::check($new_password, auth()->user()->password)) {
    //             $user = User::where('id', auth()->user()->id)->update([
    //                 'password' => Hash::make($new_password)
    //             ]);
    //             $message = 'Password Updated successfully';
    //         } else {
    //             $message = 'Password Same as Before';
    //             return apiResponse(
    //                 data: [
    //                     'message' => $message,
    //                 ],
    //                 message: $message,
    //                 status: 'error',
    //                 statusCode: 422
    //             );
    //         }
    //     } else {
    //         $message = 'Current Password Doesn\'t match';
    //         return apiResponse(
    //             data: [
    //                 'message' => $message,
    //             ],
    //             message: $message,
    //             status: 'error',
    //             statusCode: 422
    //         );
    //     }

    //     return $this->apiResponse(
    //         data: [
    //             'message' => $message
    //         ]
    //     );
    // }
}
