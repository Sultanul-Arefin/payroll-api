<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    public function sahin()
    {
        return 'dhore fel';
    }
    /**
     * Handle an incoming registration request.
     *
     * @param RegisterRequest $request
     *
     * @return JsonResponse
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // event(new Registered($user));

        return apiResponse(
            data: [
                'email' => $user->email,
                'name' => $user->name,
                'token' => $user->createToken($user->name)->plainTextToken
            ],
            message: 'User registered successfully'
        );
    }
}
