<?php

namespace Modules\SalaryItemsCategory\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalaryItemsCategory\Http\Resources\SalaryItemsCategoryResource;
use Modules\SalaryItemsCategory\Repositories\Interfaces\SalaryItemsCategoryInterface;

class SalaryItemsCategoryController extends Controller
{
    public function __construct(
        private SalaryItemsCategoryInterface $salaryItemsCategoryRepo
    ){
    }

    public function index()
    {
        $rows = 15;
        if (request()?->has('rows')) {
            $rows = (int) request('rows');
        }

        return SalaryItemsCategoryResource::collection(
            $this->salaryItemsCategoryRepo->allWithSearch(
                ['*'],
                [],
                $rows
            )
        );
    }
}
