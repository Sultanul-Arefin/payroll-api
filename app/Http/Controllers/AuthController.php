<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Contracts\Activity;

class AuthController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
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
                'token' => $user->createToken($user->name)->plainTextToken
            ],
            message: 'User logged in successful'
        );
    }
}
