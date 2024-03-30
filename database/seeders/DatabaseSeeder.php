<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Package\Database\Seeders\PackageDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            // RoleAndPermissionSeeder::class,
            PackageDatabaseSeeder::class,
            UserSeeder::class,
            AdminUserSeeder::class,
            SalaryItemsCategorySeeder::class,
            SalaryItemsNamesSeeder::class,
            EmployeeSalaryItemsSeeder::class,
            LeaveSalaryItemsSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
        ]);
    }
}
