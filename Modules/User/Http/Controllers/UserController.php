<?php

namespace Modules\User\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Traits\Attachment;
use App\Http\Traits\ImageUploads;
use App\Models\User;
use App\Notifications\UserCreateMailFailedNotification;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;
use Modules\SalaryItemsName\Entities\SalaryItemsName;
use Modules\User\Entities\UserAttachment;
use Modules\User\Entities\UserDetails;
use Modules\User\Http\Requests\UserStoreRequest;
use Modules\User\Http\Requests\UserUpdateRequest;
use Modules\User\Http\Resources\UserBasicSalaryResource;
use Modules\User\Http\Resources\UserResource;
use Modules\User\Http\Traits\CountryTrait;
use Modules\User\Http\Traits\UserTrait;
use Modules\User\Jobs\UserCreateMailJob;
use Modules\User\Notifications\UserCreatedNotificationToAdmin;
use Modules\User\Notifications\UserCreatedNotificationToUser;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class UserController extends Controller
{
    use ImageUploads,
        Attachment,
        UserTrait,
        CountryTrait;

    /**
     * Display a listing of the resource.
     *
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
                    'user_details',
                ],
                $rows
            )
        );
    }

    public function findById(User $user)
    {
        $user->user_details = $user->user_details;
        $user->user_details['gender_value'] = $user->user_details?->gender == 0 ? 'Female' : 'Male';
        $user->user_details['user_image'] = $user->user_details?->user_image ? env('APP_URL').'/'.'storage/'.$user->user_details?->user_image : null;
        $user->department_name = $user->department?->department_name;
        $user->designation_name = $user->designation?->name;
        $user->assign_to_name = $user->assign_to_user?->name;
        $user->user_attachments = $user->user_attachments;
        $items = SalaryItemsName::query()
            ->whereHas('employeeSalaryItem', function (Builder $builder) use ($user) {
                $builder->where('employee_id', $user->id);
            })
            // ->where('salary_items_category_id', 1)
            ->get();
        $user->salary_items = $items->map(function ($item) use ($user) {
            return new UserBasicSalaryResource($item, $user->id);
        });

        return apiResponse(
            data: $user,
            message: 'Successfully get User',
            status: 'success'
        );
    }

    public function get_customer_id(): JsonResponse
    {
        $count = User::where('company_id', auth()->user()->company_id)->count();
        return apiResponse(
            data: [
                'customer_id' => 'EMP-' . $count + 1
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Renderable
     */
    public function store(UserStoreRequest $request) : JsonResponse
    {
        if (! $request->has('wages')) {
            $request->merge(['wages' => null]);
        }
        try {
            $allFile = []; // all file name store into array
            $message = DB::transaction(function () use ($request, $allFile) {

                $user = $this->user_repo->create([
                    'designation_id' => $request->designation_id,
                    'assign_to' => $request->assign_to,
                    'department_id' => $request->department_id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'company_id' => auth()->user()->company_id,
                    'status' => User::USER_ACTIVE,
                    'customer_id' => $request->customer_id,
                    'employee_type' => $request->employee_type,
                ]);

                $user_details = UserDetails::create([
                    'user_id' => $user->id,
                    'user_area' => $request->user_area,
                    'user_city' => $request->user_city,
                    'zip_code' => $request->zip_code,
                    'country_id' => $request->country_id,
                    'user_phone' => $request->user_phone,
                    'gender' => $request->gender,
                    'nid' => $request->nid,
                    'date_of_birth' => $request->date_of_birth,
                    'joining_date' => $request->joining_date,
                    'payment_type' => $request->payment_type,
                    'bank_name' => $request->bank_name,
                    'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code,
                    'bank_iban_or_account_no' => $request->bank_iban_or_account_no,
                    'tin' => $request->tin,
                    'user_image' => $this->imageUpload($request, UserDetails::USER_IMAGE_PATH),
                    'payslip_type' => $request->payslip_type == UserDetails::FRENCH_PAYSLIP ? UserDetails::FRENCH_PAYSLIP : UserDetails::UNIVERSAL_PAYSLIP,
                    'attendance_type' => $request->attendance_type == UserDetails::MACHINE_ATTENDANCE ? UserDetails::MACHINE_ATTENDANCE : UserDetails::WEB_ATTENDANCE,
                ]);
                //file one
                if ($request->hasFile('contract_letter')) {
                    $allFileName = $this->user_repo->userDocument($request->contract_letter, 'contract', 'contract_letter', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file two
                if ($request->hasFile('national_id_card')) {
                    $allFileName = $this->user_repo->userDocument($request->national_id_card, 'contract', 'national_id_card', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file three
                if ($request->hasFile('cv')) {
                    $allFileName = $this->user_repo->userDocument($request->cv, 'contract', 'cv', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file four
                if ($request->hasFile('change_contract_letter')) {
                    $allFileName = $this->user_repo->userDocument($request->change_contract_letter, 'contract', 'change_contact_letter', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file five
                if ($request->hasFile('passports')) {
                    $allFileName = $this->user_repo->userDocument($request->passport, 'official', 'passport', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file six
                if ($request->hasFile('visa')) {
                    $allFileName = $this->user_repo->userDocument($request->visa, 'official', 'visa', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file seven
                if ($request->hasFile('work_permit')) {
                    $allFileName = $this->user_repo->userDocument($request->work_permit, 'official', 'work_permit', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file eight
                if ($request->hasFile('other_docs_1')) {
                    $allFileName = $this->user_repo->userDocument($request->other_docs_1, 'others', 'other_docs_1', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file ten
                if ($request->hasFile('other_docs_2')) {
                    $allFileName = $this->user_repo->userDocument($request->other_docs_2, 'others', 'other_docs_2', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file eleven
                if ($request->hasFile('other_docs_3')) {
                    $allFileName = $this->user_repo->userDocument($request->other_docs_3, 'others', 'other_docs_3', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file nine
                if ($request->hasFile('other_docs_4')) {
                    $allFileName = $this->user_repo->userDocument($request->other_docs_4, 'others', 'other_docs_4', $user->id);
                    array_push($allFile, $allFileName);
                }
                //file nine
                if ($request->hasFile('other_docs_5')) {
                    $allFileName = $this->user_repo->userDocument($request->other_docs_5, 'others', 'other_docs_5', $user->id);
                    array_push($allFile, $allFileName);
                }

                // add salary items
                $this->add_salary_items($request->all(), $user->id);
                try {
                    // UserCreateMailJob::dispatch($request->name, $user->password, $user->email);

                    // Created Notification
                    $user->notify(new UserCreatedNotificationToUser(auth()->user(), $user));
                    auth()->user()->notify(new UserCreatedNotificationToAdmin(auth()->user(), $user));

                    return 'Successfully sent';
                } catch (Exception $e) {
                    $user->notify(new UserCreateMailFailedNotification($request->email));

                    return null;
                }
            });
        } catch (\Exception $ex) {
            //$this->deleteMultipleAttachment($allFile); // if exception throw, it will call and delete recent stored file form storage
            throw new CustomException($ex->getMessage(), 200);
        }

        return apiResponse(
            data: null,
            message: $message ? "Successfully created user, you'll be notified shortly through email" : 'Mail could not sent',
            status: 'success'
        );
    }

    public function add_salary_items($salary_items, $employee_id)
    {
        if ($salary_items['wages'] == null) {
            $item_id = $this->check_salary_items('Wages');
            $this->add_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if ($salary_items['wages']) {
            $item_id = $this->check_salary_items('Wages');
            $this->add_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if ($salary_items['ordinary_time_rate']) {
            $item_id = $this->check_salary_items('Ordinary Time Rate');
            $this->add_items_with_employee($item_id, $salary_items['ordinary_time_rate'], $employee_id);
        }
        if ($salary_items['maternity_time_rate']) {
            $item_id = $this->check_salary_items('Maternity Time Rate');
            $this->add_items_with_employee($item_id, $salary_items['maternity_time_rate'], $employee_id);
        }
        if ($salary_items['paid_sick_leave_rate']) {
            $item_id = $this->check_salary_items('Paid Sick Leave Rate');
            $this->add_items_with_employee($item_id, $salary_items['paid_sick_leave_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items('Sick Leave');
            $this->add_items_with_employee($item_id, $salary_items['paid_sick_leave_rate'], $employee_id);
        }
        if ($salary_items['unpaid_sick_leave_rate']) {
            $item_id = $this->check_salary_items('Unpaid Sick Leave Rate');
            $this->add_items_with_employee($item_id, $salary_items['unpaid_sick_leave_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items('Unpaid Sick Leave');
            $this->add_items_with_employee($item_id, $salary_items['unpaid_sick_leave_rate'], $employee_id);
        }
        if ($salary_items['holiday_rate']) {
            $item_id = $this->check_salary_items('Holiday Rate');
            $this->add_items_with_employee($item_id, $salary_items['holiday_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items('Annual Leave');
            $this->add_items_with_employee($item_id, $salary_items['holiday_rate'], $employee_id);
        }
        if ($salary_items['absent']) {
            $item_id = $this->check_salary_items('Absent Rate');
            $this->add_items_with_employee($item_id, $salary_items['absent'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items('Absent');
            $this->add_items_with_employee($item_id, $salary_items['absent'], $employee_id);
        }
        if ($salary_items['bonus']) {
            $item_id = $this->check_salary_items('Bonus');
            $this->add_items_with_employee($item_id, $salary_items['bonus'], $employee_id);
        }
        if ($salary_items['overtime_rate']) {
            $item_id = $this->check_salary_items('Overtime Rate');
            $this->add_items_with_employee($item_id, $salary_items['overtime_rate'], $employee_id);
        }
        if ($salary_items['double_overtime_rate']) {
            $item_id = $this->check_salary_items('Double Overtime Rate');
            $this->add_items_with_employee($item_id, $salary_items['double_overtime_rate'], $employee_id);
        }
        if ($salary_items['recuperated_hour']) {
            $item_id = $this->check_salary_items('Recuperated Hour');
            $this->add_items_with_employee($item_id, $salary_items['recuperated_hour'], $employee_id);
        }
    }

    public function check_salary_items($item_name)
    {
        $name = SalaryItemsName::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', 'like', $item_name)
            ->first();

        return $name->id;
    }

    public function add_items_with_employee($item_id, $amount, $employee_id)
    {
        EmployeeSalaryItem::create([
            'salary_item_id' => $item_id,
            'employee_id' => $employee_id,
            'company_id' => auth()->user()->company_id,
            'amount' => $amount,
        ]);
    }

    // update salary items
    public function update_salary_items($salary_items, $employee_id)
    {
        if ($salary_items['wages'] == null) {
            $item_id = $this->check_salary_items_for_update('Wages');
            $this->update_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if ($salary_items['wages']) {
            $item_id = $this->check_salary_items_for_update('Wages');
            $this->update_items_with_employee($item_id, $salary_items['wages'], $employee_id);
        }
        if ($salary_items['ordinary_time_rate']) {
            $item_id = $this->check_salary_items_for_update('Ordinary Time Rate');
            $this->update_items_with_employee($item_id, $salary_items['ordinary_time_rate'], $employee_id);
        }
        if ($salary_items['maternity_time_rate']) {
            $item_id = $this->check_salary_items_for_update('Maternity Time Rate');
            $this->update_items_with_employee($item_id, $salary_items['maternity_time_rate'], $employee_id);
        }
        if ($salary_items['paid_sick_leave_rate']) {
            $item_id = $this->check_salary_items_for_update('Paid Sick Leave Rate');
            $this->update_items_with_employee($item_id, $salary_items['paid_sick_leave_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items_for_update('Sick Leave');
            $this->update_items_with_employee($item_id, $salary_items['paid_sick_leave_rate'], $employee_id);
        }
        if ($salary_items['unpaid_sick_leave_rate']) {
            $item_id = $this->check_salary_items_for_update('Unpaid Sick Leave Rate');
            $this->update_items_with_employee($item_id, $salary_items['unpaid_sick_leave_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items_for_update('Unpaid Sick Leave');
            $this->update_items_with_employee($item_id, $salary_items['unpaid_sick_leave_rate'], $employee_id);
        }
        if ($salary_items['holiday_rate']) {
            $item_id = $this->check_salary_items_for_update('Holiday Rate');
            $this->update_items_with_employee($item_id, $salary_items['holiday_rate'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items_for_update('Annual Leave');
            $this->update_items_with_employee($item_id, $salary_items['holiday_rate'], $employee_id);
        }
        if ($salary_items['absent']) {
            $item_id = $this->check_salary_items_for_update('Absent Rate');
            $this->update_items_with_employee($item_id, $salary_items['absent'], $employee_id);

            // for category id 2
            $item_id = $this->check_salary_items_for_update('Absent');
            $this->update_items_with_employee($item_id, $salary_items['absent'], $employee_id);
        }
        if ($salary_items['bonus']) {
            $item_id = $this->check_salary_items_for_update('Bonus');
            $this->update_items_with_employee($item_id, $salary_items['bonus'], $employee_id);
        }
        if ($salary_items['overtime_rate']) {
            $item_id = $this->check_salary_items_for_update('Overtime Rate');
            $this->update_items_with_employee($item_id, $salary_items['overtime_rate'], $employee_id);
        }
        if ($salary_items['double_overtime_rate']) {
            $item_id = $this->check_salary_items_for_update('Double Overtime Rate');
            $this->update_items_with_employee($item_id, $salary_items['double_overtime_rate'], $employee_id);
        }
        if ($salary_items['recuperated_hour']) {
            $item_id = $this->check_salary_items_for_update('Recuperated Hour');
            $this->update_items_with_employee($item_id, $salary_items['recuperated_hour'], $employee_id);
        }
    }

    public function check_salary_items_for_update($item_name)
    {
        $name = SalaryItemsName::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('name', 'like', $item_name)
            ->first();
        return $name->id;
    }

    public function update_items_with_employee($item_id, $amount, $employee_id)
    {
        EmployeeSalaryItem::where('salary_item_id', $item_id)->where('employee_id', $employee_id)->where('company_id', auth()->user()->company_id)->update([
            'amount' => $amount,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Renderable
     */
    public function update(UserUpdateRequest $request, User $user)
    {
      //  DB::transaction(function () use ($request, $user) {

            $user_details = UserDetails::where('user_id', $user->id)->first();

            $user_image = $user_details->user_image;
            if ($request->has('user_image')) {
                // unlink goes here
                if ($user_details->user_image && $this->isImageExist($user_details->user_image)) {
                    $this->deleteImage($user_details->user_image);
                }
                $user_image = $this->imageUpload($request, UserDetails::USER_IMAGE_PATH);
            }
            $user_update = $this->user_repo->update($user->id, [
                'name' => $request->name ?? $user->name,
                'email' => $request->email ?? $user->email,
                'designation_id' => $request->designation_id ?? $user->designation_id,
                'department_id' => $request->department_id ?? $user->department_id,
                'assign_to' => $request->assign_to ?? $user->assign_to,
                'employee_type' => $request->employee_type ?? $user->employee_type,
                'company_id' => auth()->user()->company_id,
            ]);

            $user_details = $this->user_repo->userDetailsUpdate($user->id, [
                'user_area' => $request->user_area ?? $user->user_details->user_area,
                'user_phone' => $request->user_phone ?? $user->user_details->user_phone,
                'user_city' => $request->user_city ?? $user->user_details->user_city,
                'zip_code' => $request->zip_code ?? $user->user_details->zip_code,
                'country_id' => $request->country_id ?? $user->user_details->country_id,
                'gender' => $request->gender ?? $user->user_details->gender,
                'passport' => $request->passport ?? $user->user_details->passport,
                'date_of_birth' => $request->date_of_birth ?? $user->user_details->date_of_birth,
                'joining_date' => $request->joining_date ?? $user->user_details->joining_date,
                'bank_name' => $request->bank_name ?? $user->user_details->bank_name,
                'bank_bic_or_swift_code' => $request->bank_bic_or_swift_code ?? $user->user_details->bank_bic_or_swift_code,
                'bank_iban_or_account_no' => $request->bank_iban_or_account_no ?? $user->user_details->bank_iban_or_account_no,
                'tin' => $request->tin ?? $user->user_details->tin,
                'user_image' => $user_image,
                'payslip_type' => $request->payslip_type ?? $user->user_details->payslip_type,
                'attendance_type' => $request->attendance_type ?? $user->user_details->attendance_type,
            ]);

            $allFileArr = [];
            //all requested files are storing into empty associative array like [input name => file]
            foreach ($request->allFiles() as $key => $file) {
                $allFileArr[$key] = $file;
            }

            //existing files are update
            if ($user->userAttachment) {
                //  return 'inside attachment' ;
                //loop all existing file from userAttachment DB under a user
                foreach ($user->userAttachment as $value) {
                    foreach ($allFileArr as $key => $file) { //loop all requested files from new associative array
                        //check existing DB item_type and requested file_name type are same or not,  and existing heading_type equal 1 or not
                        if ($value->item_type === (isset(UserAttachment::CONTRACT_ITEM_TYPE[$key]) ? UserAttachment::CONTRACT_ITEM_TYPE[$key] : null) && $value->heading_type === 1) {
                            $this->deleteAttachment($value->file_name);
                            $value->update([
                                'file_name' => $this->updateAttachment($file,  ("employees/{$user->id}/contract")),
                            ]);
                            unset($allFileArr[$key]); //remove element from new array
                        }
                        if ($value->item_type === (isset(UserAttachment::OFFICIAL_ITEM_TYPE[$key]) ? UserAttachment::OFFICIAL_ITEM_TYPE[$key] : null) && $value->heading_type === 2) {
                            $this->deleteAttachment($value->file_name);
                            $value->update([
                                'file_name' => $this->updateAttachment($file, ("employees/{$user->id}/official")),
                            ]);
                            unset($allFileArr[$key]); //remove element from new array
                        }
                        if ($value->item_type === (isset(UserAttachment::OTHERS_ITEM_TYPE[$key]) ? UserAttachment::OTHERS_ITEM_TYPE[$key] : null) && $value->heading_type === 3) {
                            $this->deleteAttachment($value->file_name);
                            $value->update([
                                'file_name' => $this->updateAttachment($file, ("employees/{$user->id}/others")),
                            ]);
                            unset($allFileArr[$key]);
                        }
                    }
                }
            }
            //new requested files are stored
            foreach ($allFileArr as $key => $file) {

                if ($key === 'contract_letter' || $key === 'national_id_card' || $key === 'cv' || $key === 'change_contract_letter') {
                    $this->user_repo->userDocument($file, 'contract', $key, $user->id);
                }
                if ($key === 'passport_file' || $key === 'visa' || $key === 'work_permit' || $key === 'immigration_application') {
                    $this->user_repo->userDocument($file, 'official', $key, $user->id);
                }
                if ($key === 'other_docs_1' || $key === 'other_docs_2' || $key === 'other_docs_3' || $key === 'other_docs_4' || $key === 'other_docs_5') {
                    $this->user_repo->userDocument($file, 'others', $key, $user->id);
                }
            }

            // update salary items
            if(isset($request->ordinary_time_rate) && $request->ordinary_time_rate != "null"){
                $this->update_salary_items($request->all(), $user->id);
            }

       // });

        return apiResponse(
            data: null,
            message: 'Employee Profile Successfully Updated',
            status: 'success'
        );
    }

    public function validate_employee_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        $check_email = User::query()
                    ->where('email', $request->email)
                    ->first();
        if($check_email)
        {
            return apiResponse(
                data: null,
                message: 'Email Already Exists',
                status: 'error'
            );
        }
        return apiResponse(
            data: null,
            message: 'Email is ok to update',
            status: 'success'
        );
    }

    /**
     * employee active or inactive
     */
    public function userStatus(User $user, Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|integer|in:0,1',
        ]);
        $this->user_repo->userStatus($user, $request->status);

        return apiResponse(null, 'Successfully Employee Status Updated', 'success');
    }

    public function storeDocument(Request $request)
    {
        $request->validate([
            'file_name' => 'required|file|max:2048|mimes:jpg,png,pdf,docx',
            'heading_type' => 'required',
            'item_type' => 'required',
        ]);
        if ($this->user_repo->userDocument($request)) {
            return apiResponse(
                data: null,
                message: 'Successfully Document Stored',
                status: 'success'
            );
        }
    }

    public function deleteDocument($id)
    {
        $userAttachment = UserAttachment::find($id);
        if ($this->deleteAttachment($userAttachment->file_name)) {
            $userAttachment->delete();

            return apiResponse(null, 'Successfully Employee Document Deleted', 'success');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function user_status_update(Request $request, User $user)
    {

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|integer|in:0,1',
        ]);

        $updateStatus = User::find($user->id);

        $status = User::where('id', $user->id)->update([

            'status' => $request->status,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'status Change Successfully',

        ], 200);
    }
}
