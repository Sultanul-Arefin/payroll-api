<?php

namespace Modules\User\Http\Controllers;

use App\Http\Traits\ImageUploads;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\User\Emails\SendPassword;
use Modules\User\Entities\UserDetails;
use Modules\User\Http\Requests\UserStoreRequest;
use Modules\User\Http\Requests\UserUpdateRequest;
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
        $message = DB::transaction(function ()use($request){
            
            $user = $this->user_repo->create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'company_id' => auth()->user()->company_id,
                        'password' => Hash::make($request->password),
                    ]);
            $user->assignRole('employee');
            
            $user_details = UserDetails::create([
                'user_id' => $user->id,
                'user_address' => $request->user_address,
                'user_phone' => $request->user_phone,
                'user_image' => $this->imageUpload($request,UserDetails::USER_IMAGE_PATH),
            ]);
            
            try{
                Mail::to($user->email)->send(new SendPassword($request->password,$user->name));
                return "Successfully sent";

            }catch(Exception $e){
                return null;
            }
           

            
        });
        

        return apiResponse(
            data: null,
            message: $message ? "Successfully created user": "Mail could not sent",
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
    public function update(UserUpdateRequest $request, User $user)
    {
        DB::transaction(function ()use($request,$user){

            $user_image = $user->user_image;

            if($request->file('user_image')){
                // unlink goes here
                if($this->isImageExist($user->user_image)){
                    $this->deleteImage($user->user_image);
                }
                $user_image = $this->imageUpload($request,UserDetails::USER_IMAGE_PATH);
            }

            $user = $this->user_repo->update($user->id,[
                        'name' => $request->name,
                        'email' => $request->email,
                    ]);

            // update the roles
            $prev_role = $user->getRoleNames();
            if($prev_role){
                $user->syncRoles($request->role);
            }
            
            $user_details = UserDetails::find($user->id)->update([
                'user_address' => $request->user_address,
                'user_phone' => $request->user_phone,
                'user_image' => $user_image,
            ]);
        });
        

        return apiResponse(
            data: null,
            message: 'Successfully updated',
            status: 'success'
        );
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