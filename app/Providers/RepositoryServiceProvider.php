<?php

namespace App\Providers;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Support\ServiceProvider;
use Modules\Company\Repositories\Classes\CompanyRepository;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;
use Modules\Department\Repositories\Classes\DepartmentRepository;
use Modules\Department\Repositories\Interfaces\DepartmentRepositoryInterface;
use Modules\User\Repositories\Classes\UserRepository;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use Modules\Package\Repositories\Classes\PackageRepository;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;
use Modules\SalaryItemsCategory\Repositories\Classes\SalaryItemsCategoryRepository;
use Modules\SalaryItemsCategory\Repositories\Interfaces\SalaryItemsCategoryInterface;
use Modules\SalaryItemsName\Repositories\Classes\SalaryItemsNameRepository;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}