<?php

namespace Modules\Auth\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Company\Entities\Company;
use Modules\User\Entities\UserDetails;

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
        $user = DB::transaction(function() use($request){
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $company = Company::create([
                'company_name' => $request->company_name,
                'company_email' => $request->company_email,
                'company_phone' => $request->company_phone,
            ]);
            $user->update([
                'company_id' => $company->id
            ]);
            DB::table('company_associated_with_package')->insert([
                'company_id' => $company->id,
                'package_id' => $request->package_id
            ]);
            UserDetails::create([
                'user_id' => $user->id,
                'user_area' => $request->user_area,
                'user_city' => $request->user_city,
                'user_phone' => $request->user_phone,
                // 'user_image' => $request->user_address ?? null,
            ]);
            $user->assignRole('super-admin');
            // add project columns
            project_columns_seeder($user, $company);
            return $user;
        });

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