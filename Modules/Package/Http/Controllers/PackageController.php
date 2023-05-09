<?php

namespace Modules\Package\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Package\Http\Resources\PackageResource;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;

class PackageController extends Controller
{
    public function __construct(
        private PackageRepositoryInterface $packageRepo
    )
    {
    }

    /**
     * @return mixed
     */
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
}
