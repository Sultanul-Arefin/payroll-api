<?php

namespace App\Providers;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Support\ServiceProvider;
use Modules\Attendance\Repositories\Classes\AttendanceRepository;
use Modules\Attendance\Repositories\Interfaces\AttendanceRepositoryInterface;
use Modules\Company\Repositories\Classes\CompanyRepository;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;
use Modules\Department\Repositories\Classes\DepartmentRepository;
use Modules\Department\Repositories\Interfaces\DepartmentRepositoryInterface;
use Modules\Designation\Repositories\Classes\DesignationRepository;
use Modules\Designation\Repositories\Interfaces\DesignationInterface;
use Modules\LeaveManagement\Repositories\Classes\LeaveRepository;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\Package\Repositories\Classes\PackageRepository;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;
use Modules\Payslip\Repositories\Classes\PayslipRepository;
use Modules\Payslip\Repositories\Interfaces\PayslipRepositoryInterface;
use Modules\ProjectManagement\Repositories\Classes\ProjectColumnRepository;
use Modules\ProjectManagement\Repositories\Classes\ProjectRepository;
use Modules\ProjectManagement\Repositories\Classes\TaskRepository;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectColumnInterface;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectInterface;
use Modules\ProjectManagement\Repositories\Interfaces\TaskInterface;
use Modules\SalaryItemsCategory\Repositories\Classes\SalaryItemsCategoryRepository;
use Modules\SalaryItemsCategory\Repositories\Interfaces\SalaryItemsCategoryInterface;
use Modules\SalaryItemsName\Repositories\Classes\SalaryItemsNameRepository;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;
use Modules\User\Repositories\Classes\UserRepository;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            BaseRepositoryInterface::class,
            BaseRepository::class
        );

        $this->app->bind(
            CompanyRepositoryInterface::class,
            CompanyRepository::class
        );

        $this->app->bind(
            PackageRepositoryInterface::class,
            PackageRepository::class
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            SalaryItemsCategoryInterface::class,
            SalaryItemsCategoryRepository::class
        );

        $this->app->bind(
            SalaryItemsNameInterface::class,
            SalaryItemsNameRepository::class
        );

        $this->app->bind(
            ProjectInterface::class,
            ProjectRepository::class
        );

        $this->app->bind(
            ProjectColumnInterface::class,
            ProjectColumnRepository::class
        );

        $this->app->bind(
            TaskInterface::class,
            TaskRepository::class
        );

        $this->app->bind(
            AttendanceRepositoryInterface::class,
            AttendanceRepository::class
        );

        $this->app->bind(
            DesignationInterface::class,
            DesignationRepository::class
        );

        $this->app->bind(
            LeaveRepositoryInterface::class,
            LeaveRepository::class
        );

        $this->app->bind(
            PayslipRepositoryInterface::class,
            PayslipRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
