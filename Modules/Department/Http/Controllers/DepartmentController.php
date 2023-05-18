<?php

namespace Modules\Department\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Department\Entities\Department;
use Modules\Department\Http\Requests\StoreDepartment;
use Modules\Department\Http\Requests\UpdateDepartment;
use Modules\Department\Http\Resources\AllDepartmentResource;
use Modules\Department\Http\Resources\DepartmentResource;
use Modules\Department\Repositories\Interfaces\DepartmentRepositoryInterface;

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
                [],
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
}
