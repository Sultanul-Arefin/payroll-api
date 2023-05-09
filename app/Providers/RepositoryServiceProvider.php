<?php

namespace App\Providers;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Support\ServiceProvider;
use Modules\Company\Repositories\Classes\CompanyRepository;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;
use Modules\Package\Repositories\Classes\PackageRepository;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
