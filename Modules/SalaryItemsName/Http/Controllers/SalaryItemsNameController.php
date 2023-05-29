<?php

namespace Modules\SalaryItemsName\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalaryItemsName\Http\Resources\SalaryItemsNameResource;
use Modules\SalaryItemsName\Repositories\Interfaces\SalaryItemsNameInterface;

class SalaryItemsNameController extends Controller
{
    public function __construct(
        private SalaryItemsNameInterface $salaryItemsNameRepo
    ){
    }

    public function index($id)
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return SalaryItemsNameResource::collection(
            $this->salaryItemsNameRepo->allWithSearch(
                $id,
                ['*'],
                [],
                $rows
            )
        );
    }
}
