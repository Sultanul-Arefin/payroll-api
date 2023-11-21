<?php

namespace Modules\SalaryItemsName\Http\Services;

use Modules\SalaryItemsName\Entities\LeaveSalaryItems;

class SalaryLeaveItemsService
{
    public function create($data)
    {
        return LeaveSalaryItems::create([
            'salary_items_id' => $data->id,
        ]);
    }
}
