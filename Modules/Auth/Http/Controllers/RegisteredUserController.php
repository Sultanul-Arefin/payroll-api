<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Services\UserServices;
use Modules\Company\Entities\Company;
use Modules\Package\Entities\Package;
use Modules\User\Entities\UserDetails;

class RegisteredUserController extends Controller
{
    public function __construct(
        private UserServices $userServices
    ) {
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'status' => User::USER_ACTIVE,
                'staff_interaction_panel_status' => User::STAFF_INTERACTION_PANEL_GIVEN,
                'role_id' => User::ADMIN,
                'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME,
            ]);
            $company = Company::create([
                'company_name' => $request->company_name,
                'company_email' => $request->company_email,
                'company_phone' => $request->company_phone,
                'white_label_id' => 1
            ]);
            $user->update([
                'company_id' => $company->id,
            ]);
            DB::table('company_associated_with_package')->insert([
                'company_id' => $company->id,
                'package_id' => $request->package_id,
            ]);
            UserDetails::create([
                'user_id' => $user->id,
                'user_area' => $request->user_area,
                'user_city' => $request->user_city,
                'user_phone' => $request->user_phone,
                'country_id' => $request->country_id,
                // 'user_image' => $request->user_address ?? null,
            ]);
            // $user->assignRole('super-admin');

            // seeding the database
            $this->userServices->salary_items_name_seeder($company->id);
            $this->userServices->leave_salary_items($company->id);
            $this->userServices->department_seeder($company->id);
            $this->userServices->designation_seeder($company->id);

            return $user;
        });

        // event(new Registered($user));

        return apiResponse(
            data: [
                'email' => $user->email,
                'name' => $user->name,
                'token' => $user->createToken($user->name)->plainTextToken,
            ],
            message: 'User registered successfully'
        );
    }

    public function registration_from_bfin_technology(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
            'phone' => 'required',
            'service_name' => 'required'
        ]);
        try{
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                    'status' => User::USER_ACTIVE,
                    'staff_interaction_panel_status' => User::STAFF_INTERACTION_PANEL_GIVEN,
                    'role_id' => User::ADMIN,
                    'employee_type' => User::EMPLOYEE_TYPE_FULL_TIME,
                ]);
                $company = Company::create([
                    'company_name' => $request->name,
                    'company_email' => $request->email,
                    'company_phone' => $request->phone,
                    'white_label_id' => 1
                ]);
                $user->update([
                    'company_id' => $company->id,
                ]);
                $package = Package::where('package_name', 'like', '%' . $request->service_name . '%')->first();
                if (!$package) {
                    throw new \Exception('Package not found');
                }
                DB::table('company_associated_with_package')->insert([
                    'company_id' => $company->id,
                    'package_id' => $package->id,
                ]);
                UserDetails::create([
                    'user_id' => $user->id,
                    'user_area' => $request->address,
                    'user_city' => $request->address,
                    'user_phone' => $request->address,
                    'country_id' => 1,
                ]);

                // seeding the database
                $this->userServices->salary_items_name_seeder($company->id);
                $this->userServices->leave_salary_items($company->id);
                $this->userServices->department_seeder($company->id);
                $this->userServices->designation_seeder($company->id);

                return $user;
            });
        } catch(\Exception $e){ // Handle the exception
            // Log the error, return a response
            Log::error('Error occurred while creating user and company: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }

        // event(new Registered($user));

        return apiResponse(
            data: [
                'email' => $user?->email,
                'name' => $user?->name,
                'token' => $user?->createToken($user->name)?->plainTextToken,
            ],
            message: 'User registered successfully'
        );
    }
}
