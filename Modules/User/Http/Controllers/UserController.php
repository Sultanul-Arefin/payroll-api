<?php

namespace Modules\User\Http\Controllers;

use App\Http\Traits\ImageUploads;
use App\Models\User;
use App\Notifications\UserCreateMailFailedNotification;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\User\Emails\SendPassword;
use Modules\User\Entities\UserAttachment;
use Modules\User\Entities\UserDetails;
use Modules\User\Http\Requests\UserStoreRequest;
use Modules\User\Http\Requests\UserUpdateRequest;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Jobs\UserCreateMailJob;
use App\Http\Traits\Attachment;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsName\Entities\SalaryItemsName;
use Modules\User\Http\Traits\UserTrait;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class UserController extends Controller
{
    use ImageUploads,
        Attachment,
        UserTrait;
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



    public function findById(User $user){
        $user->user_details = $user->user_details;
        $user->user_details['gender_value'] = $user->user_details?->gender == 0 ? 'Female' : 'Male';
        $user->department_name = $user->department?->department_name;
        $user->designation_name = $user->designation?->name;
        $user->assign_to_name = $user->assign_to_user?->name;
        return apiResponse(
            data: $user,
            message:"Successfully get User",
            status: 'success'
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
                        'designation_id' => $request->designation_id,
                        'assign_to' => $request->assign_to,
                        'department_id' => $request->department_id,
                        'name' => $request->name,
                        'email' => $request->email,
                        'company_id' => auth()->user()->company_id,
                        'password' => $request->password,
                        'user_role' => User::EMPLOYEE
                    ]);
            $user->assignRole('employee');

            $user_details = UserDetails::create([
                'user_id' => $user->id,
                'user_area' => $request->user_area,
                'user_city' => $request->user_city,
                'zip_code' => $request->zip_code,
                'country_id' => $request->country_id,
                'user_phone' => $request->user_phone,
                'gender' => $request->gender,
                'nid' => $request->nid,
                'passport' => "assport",
                'date_of_birth' => $request->date_of_birth,
                'joining_date' => $request->joining_date,
                'payment_type' => $request->payment_type,
                'bank_name' => $request->bank_name,
                'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code,
                'bank_iban_or_account_no' => $request->bank_iban_or_account_no,
                'tin' => $request->tin,
                'user_image' => $this->imageUpload($request,UserDetails::USER_IMAGE_PATH),
            ]);
            //multiple file store
            if($request->hasFile('file_name')){
               $allFileName =  $this->user_repo->multipleStoreAttachment($request, $user->id);//return array
            }
            // add salary items
            return $this->add_salary_items($request->all(), $user->id);
            try{
                UserCreateMailJob::dispatch($request->name, $user->password, $user->email);
                return "Successfully sent";
            }catch(Exception $e){
                $this->deleteMultipleAttachment($allFileName); // when exception throw then called delete stored storage file
                $user->notify(new UserCreateMailFailedNotification($request->email));
                return null;
            }
        });

        return apiResponse(
            data: null,
            message: $message ? "Successfully created user, you'll be notified shortly through email": "Mail could not sent",
            status: 'success'
        );
    }

    public function add_salary_items($salary_items, $employee_id)
    {
        if($salary_items['wages'] == null){
            $item_id = $this->check_salary_items('Wages');
            $this->add_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if($salary_items['wages']){
            $item_id = $this->check_salary_items('Wages');
            $this->add_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if($salary_items['ordinary_time_rate']){
            $item_id = $this->check_salary_items('Ordinary Time Rate');
            $this->add_items_with_employee($item_id, $salary_items['ordinary_time_rate'], $employee_id);
        }
        if($salary_items['maternity_time_rate']){
            $item_id = $this->check_salary_items('Maternity Time Rate');
            $this->add_items_with_employee($item_id, $salary_items['maternity_time_rate'], $employee_id);
        }
        if($salary_items['paid_sick_leave_rate']){
            $item_id = $this->check_salary_items('Paid Sick Leave Rate');
            $this->add_items_with_employee($item_id, $salary_items['paid_sick_leave_rate'], $employee_id);
        }
        if($salary_items['unpaid_sick_leave_rate']){
            $item_id = $this->check_salary_items('Unpaid Sick Leave Rate');
            $this->add_items_with_employee($item_id, $salary_items['unpaid_sick_leave_rate'], $employee_id);
        }
        if($salary_items['holiday_rate']){
            $item_id = $this->check_salary_items('Holiday Rate');
            $this->add_items_with_employee($item_id, $salary_items['holiday_rate'], $employee_id);
        }
        if($salary_items['absent']){
            $item_id = $this->check_salary_items('Absent');
            $this->add_items_with_employee($item_id, $salary_items['absent'], $employee_id);
        }
        if($salary_items['bonus']){
            $item_id = $this->check_salary_items('Bonus');
            $this->add_items_with_employee($item_id, $salary_items['bonus'], $employee_id);
        }
        if($salary_items['overtime_rate']){
            $item_id = $this->check_salary_items('Overtime Rate');
            $this->add_items_with_employee($item_id, $salary_items['overtime_rate'], $employee_id);
        }
        if($salary_items['double_overtime_rate']){
            $item_id = $this->check_salary_items('Double Overtime Rate');
            $this->add_items_with_employee($item_id, $salary_items['double_overtime_rate'], $employee_id);
        }
        if($salary_items['recuperated_hour']){
            $item_id = $this->check_salary_items('Recuperated Hour');
            $this->add_items_with_employee($item_id, $salary_items['recuperated_hour'], $employee_id);
        }
    }

    public function check_salary_items($item_name)
    {
        $name = SalaryItemsName::query()
                    ->where('company_id', auth()->user()->company_id)
                    ->where('name', 'like', '%' . $item_name . '%')
                    ->first();
        return $name->id;
    }

    public function add_items_with_employee($item_id, $amount, $employee_id)
    {
        EmployeeSalaryItem::create([
            'salary_item_id' => $item_id,
            'employee_id' => $employee_id,
            'company_id' => auth()->user()->company_id,
            'amount' => $amount
        ]);
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

            $user_details = UserDetails::where('user_id',$user->id)->first();

            $user_image = $user_details->user_image;
            if($request->has('user_image')){
                // unlink goes here
                if($user_details->user_image && $this->isImageExist($user_details->user_image)){
                    $this->deleteImage($user_details->user_image);
                }
                $user_image = $this->imageUpload($request,UserDetails::USER_IMAGE_PATH);

            }
            $user_update = $this->user_repo->update($user->id,[
                'name' => $request->name,
                'designation_id' => $request->designation_id,
                'department_id' => $request->department_id,
                'assign_to' => $request->assign_to,
                'status' => $request->status,
                'company_id' => auth()->user()->company_id,
            ]);

            // update the roles
            $prev_role = $user->getRoleNames();
            if($prev_role){
                $user->syncRoles($request->role);
            }

            $user_details = $this->user_repo->userDetailsUpdate($user->id,[
                'user_area' => $request->user_area,
                'user_phone' => $request->user_phone,
                'user_city' => $request->user_city,
                'zip_code' => $request->zip_code,
                'country_id' => $request->country_id,
                'gender' => $request->gender,
                'passport' => $request->passport,
                'date_of_birth' => $request->date_of_birth,
                'joining_date' => $request->joining_date,
                'payment_type' => $request->payment_type,
                'bank_name' => $request->bank_name,
                'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code,
                'bank_iban_or_account_no' => $request->bank_iban_or_account_no,
                'tin' => $request->tin,
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
     * employee active or inactive
     * @param User $user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userStatus(User $user, Request $request):JsonResponse
    {
        $request->validate([
            'status' => 'required|integer|in:0,1'
        ]);
       $this->user_repo->userStatus($user, $request->status);
       return apiResponse(null, 'Successfully Employee Status Updated', 'success');
    }
    public function storeDocument(Request $request)
    {
        $request->validate([
            'file_name' => 'required|file|max:2048|mimes:jpg,png,pdf,docx',
            'heading_type' => 'required',
            'item_type' => 'required'
        ]);
        if($this->user_repo->userDocument($request)){
            return apiResponse(
                data: null,
                message: 'Successfully Document Stored',
                status: 'success'
            );
        }
    }
    public function deleteDocument($id){
       $userAttachment =  UserAttachment::find($id);
        if($this->deleteAttachment( $userAttachment->file_name)){
            $userAttachment->delete();
            return apiResponse(null, 'Successfully Employee Document Deleted', 'success');
        }
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

    public function user_status_update(Request $request , User $user){


        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status'=>'required|integer|in:0,1'
        ]);

        $updateStatus=User::find($user->id);

        $status=User::where('id',$user->id)->update([

            'status'=>$request->status
        ]);

        return response()->json([
            'status'=>true,
            'message'=>'status Change Successfully',


        ],200);
    }
}
