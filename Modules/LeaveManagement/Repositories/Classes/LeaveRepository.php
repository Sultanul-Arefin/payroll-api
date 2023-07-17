<?php
namespace Modules\LeaveManagement\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Support\Collection;
use Modules\LeaveManagement\Repositories\Interfaces\LeaveRepositoryInterface;
use Modules\SalaryItemsName\Entities\LeaveSalaryItems;
use Modules\SalaryItemsName\Entities\SalaryItemsName;



class LeaveRepository extends BaseRepository implements LeaveRepositoryInterface {

    /**
     * LeaveSalaryItems Repository constructor.
     *
     * @param LeaveSalaryItems $model
     */
    public function __construct(LeaveSalaryItems $model)
    {
        parent::__construct($model);
    }

    /**
     * all salary item list show under a company id
     * @return Collection|null
     */
    public function leave_types():?Collection
    {
       return SalaryItemsName::where('company_id', auth()->user()->company_id)->get();
    }

}
