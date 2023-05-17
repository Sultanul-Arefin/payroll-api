<?php

namespace Modules\User\Http\Controllers;

use App\Http\Traits\ImageUploads;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\User\Emails\SendPassword;
use Modules\User\Entities\UserDetails;
use Modules\User\Http\Requests\UserStoreRequest;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class UserController extends Controller
{
    use ImageUploads;
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function __construct(private UserRepositoryInterface $user_repo)
    {

    }
    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return UserResource::collection(
            $this->user_repo->allWithSearch(
                ['*'],
                [
                    'user_details'
                ],
                $rows
            )
        );
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('employee::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(UserStoreRequest $request)
    {
        DB::transaction(function ()use($request){
            
            $user = $this->user_repo->create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'company_id' => auth()->user()->company_id,
                        'password' => Hash::make($request->password),
                    ]);
            
            Mail::to($user->email)->send(new SendPassword($request->password,$user->name));

            $user->assignRole('employee');
            
            $user_details = UserDetails::create([
                'user_id' => $user->id,
                'user_address' => $request->user_address,
                'user_phone' => $request->user_phone,
                'user_logo' => $this->imageUpload($request,UserDetails::USER_IMAGE_PATH),
            ]);
        });
        

        return apiResponse(
            data: null,
            message: 'Successfully store',
            status: 'success'
        );
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('employee::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('employee::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}