<?php

namespace Modules\Department\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Modules\Department\Entities\Department;
use Modules\Department\Http\Requests\StoreDepartment;
use Modules\Department\Http\Requests\UpdateDepartment;
use Modules\Department\Http\Resources\AllDepartmentResource;
use Modules\Department\Http\Resources\DepartmentResource;
use Modules\Department\Notifications\DepartmentCreatedNotification;
use Modules\Department\Repositories\Interfaces\DepartmentRepositoryInterface;
use Modules\Role\Entities\Role;

class DepartmentController extends Controller
{
    public function __construct(
        private DepartmentRepositoryInterface $departmentRepo
    ){
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return DepartmentResource::collection(
            $this->departmentRepo->allWithSearch(
                ['*'],
                [
                    'departments'
                ],
                $rows
            )
        );
    }

    public function all_departments()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return AllDepartmentResource::collection(
            $this->departmentRepo->allDataWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }

    public function store(StoreDepartment $request)
    {
        $department = $this->departmentRepo->create([
            'department_name' => $request->department_name,
            'company_id' => auth()->user()->company_id,
            'parent_id' => $request->parent_id ?? null,
        ]);
        $super_admins = Role::where('name', 'super-admin')->first()->users;
        Notification::send($super_admins, new DepartmentCreatedNotification(auth()->user(), $department));

        return $this->apiResponse(
            [
                'department' => DepartmentResource::make($department->fresh())
            ],
            'Department created successfully',
            statusCode: 201
        );
    }

    public function update(
        UpdateDepartment $request,
        Department $department
    ): JsonResponse
    {
        $this->departmentRepo->update(
            $department->id,
            $request->only('department_name', 'parent_id')
        );

        return $this->apiResponse(
            [
                'department' => DepartmentResource::make($department->fresh())
            ],
            'Department updated successfully'
        );
    }

    public function destroy(Department $department){

      $users= User::where('department_id', $department->id)->get();

      
       if(count($users) > 0)
       {
            return apiResponse(
                data: null,
                message:  "Department id already exist",
                status: 'Error!'
            );
       }
            $department=Department::where('id', $department->id)->delete();
            return apiResponse(
                data: null,
                message:  "Department delete successfully",
                status: 'success!'
            );

    }
}
