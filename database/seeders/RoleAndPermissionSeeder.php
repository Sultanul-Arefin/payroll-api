<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Permission\Entities\Permission;
use Modules\Role\Entities\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'ADMIN',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'DEPARTMENT_MANAGER',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'EMPLOYEE',
            'guard_name' => 'sanctum'
        ]);

        // create permissions
        // $home = Permission::create([
        //     // home
        //     'name' => 'home',
        //     'display_name' => 'Home',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'home'
        // ]);
        // Permission::create([
        //     'name' => 'home.index',
        //     'display_name' => 'Home',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $home->id
        // ]);
        // $configuration = Permission::create([
        //     'name' => 'configuration',
        //     'display_name' => 'Configuration',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'configuration'
        // ]);
        // Permission::create([
        //     'name' => 'company.index',
        //     'display_name' => 'Company Details',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $configuration->id
        // ]);
        // Permission::create([
        //     'name' => 'department.index',
        //     'display_name' => 'Add Department',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $configuration->id
        // ]);
        // Permission::create([
        //     'name' => 'designation.index',
        //     'display_name' => 'Add Designation',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $configuration->id
        // ]);
        // $employee = Permission::create([
        //     'name' => 'employee',
        //     'display_name' => 'Employee',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'employee'
        // ]);
        // Permission::create([
        //     'name' => 'employee.index',
        //     'display_name' => 'Employee List',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $employee->id
        // ]);
        // Permission::create([
        //     'name' => 'employee.profile',
        //     'display_name' => 'Employee Profile',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $employee->id
        // ]);
        // Permission::create([
        //     'name' => 'employee.store',
        //     'display_name' => 'Employee Store',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $employee->id
        // ]);
        // Permission::create([
        //     'name' => 'employee.edit',
        //     'display_name' => 'Employee Edit',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $employee->id
        // ]);
        // Permission::create([
        //     'name' => 'employee.update',
        //     'display_name' => 'Employee Update',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $employee->id
        // ]);
        // $attendance = Permission::create([
        //     'name' => 'attendance',
        //     'display_name' => 'Attendance',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'attendance'
        // ]);
        // Permission::create([
        //     'name' => 'attendance.index',
        //     'display_name' => 'Attendance',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $attendance->id
        // ]);
        // Permission::create([
        //     'name' => 'leave',
        //     'display_name' => 'Leave',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $attendance->id
        // ]);
        // Permission::create([
        //     'name' => 'holiday',
        //     'display_name' => 'Holiday',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $attendance->id
        // ]);
        // $payroll = Permission::create([
        //     'name' => 'payroll',
        //     'display_name' => 'Payroll',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'payroll'
        // ]);
        // $project = Permission::create([
        //     'name' => 'projects',
        //     'display_name' => 'Projects',
        //     'guard_name' => 'sanctum',
        //     'display_endpoint' => 'projects'
        // ]);
        // Permission::create([
        //     'name' => 'projects.index',
        //     'display_name' => 'Projects Index',
        //     'guard_name' => 'sanctum',
        //     'parent_id' => $project->id
        // ]);

        // $role = Role::query()->create([
        //     'status' => 1,
        //     'name' => 'super-admin',
        //     'guard_name' => 'sanctum'
        // ]);
        // $role->givePermissionTo(Permission::all());
        // $role = Role::query()->create([
        //     'status' => 1,
        //     'name' => 'admin',
        //     'guard_name' => 'sanctum'
        // ]);
        // $role = Role::query()->create([
        //     'status' => 1,
        //     'name' => 'department-manager',
        //     'guard_name' => 'sanctum'
        // ]);
        // $role = Role::query()->create([
        //     'status' => 1,
        //     'name' => 'hr',
        //     'guard_name' => 'sanctum'
        // ]);
        // $role = Role::query()->create([
        //     'status' => 1,
        //     'name' => 'employee',
        //     'guard_name' => 'sanctum'
        // ]);
        // $role->givePermissionTo(Permission::whereIn('id', [1,2,7,8,12,13,14,15,16,17,18])->get());
    }
}
