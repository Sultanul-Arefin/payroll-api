<?php

namespace Modules\Package\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Package\Http\Resources\PackageResource;
use Modules\Package\Http\Resources\PackageResourceUnguarded;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;

class PackageController extends Controller
{
    public function __construct(
        private PackageRepositoryInterface $packageRepo
    ) {
    }

    public function index(): mixed
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return PackageResource::collection(
            $this->packageRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }

    public function package_list(): mixed
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return PackageResourceUnguarded::collection(
            $this->packageRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }
}
