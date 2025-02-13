<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user){
            return apiResponse(
                null,
                'You Don\'t Have Any Account',
                'error'
            );
        }


        $token = Str::random(64);

        $check_if_already_exists = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        // IF ALREADY EXISTS
        if($check_if_already_exists)
        {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->update([
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
            $url = config('app.frontend_url') . '/password-reset/' . $token .'?email=' . $request->email;
            Mail::send('emails.admin.reset-password', ['url' => $url], function($message) use($request){
                $message->to($request->email);
                $message->subject('Reset Password');
            });

            return apiResponse(
                [],
                'We have emailed your password reset link!'
            );
        }

        // IF NOT EXISTS
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
        $url = config('app.frontend_url') . '/password-reset/' . $token .'?email=' . $request->email;
        Mail::send('emails.admin.reset-password', ['url' => $url], function($message) use($request){
            $message->to($request->email);
            $message->subject('Reset Password');
        });

        return apiResponse(
            [],
            'We have emailed your password reset link!'
        );

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // $status = Password::sendResetLink(
        //     $request->only('email')
        // );

        // return $status == Password::RESET_LINK_SENT
        //     ? $this->apiResponse([], 'We have emailed your password reset link!')
        //     : throw ValidationException::withMessages([
        //         'email' => [__($status)],
        //     ]);

    }
}
