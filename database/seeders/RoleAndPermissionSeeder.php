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
        // create permissions
        $dashboard = Permission::create([
            // dashboard
            'name' => 'dashboard',
            'display_name' => 'Dashboard',
            'guard_name' => 'sanctum',
            'display_endpoint' => 'dashboard'
        ]);
        Permission::create([
            'name' => 'dashboard.index',
            'display_name' => 'Dashboard',
            'guard_name' => 'sanctum',
            'parent_id' => $dashboard->id
        ]);

        $role = Role::query()->create([
            'status' => 1,
            'name' => 'super-admin',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'admin',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'department-manager',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'hr',
            'guard_name' => 'sanctum'
        ]);
        $role = Role::query()->create([
            'status' => 1,
            'name' => 'employee',
            'guard_name' => 'sanctum'
        ]);
        $role->givePermissionTo(Permission::all());
    }
}
