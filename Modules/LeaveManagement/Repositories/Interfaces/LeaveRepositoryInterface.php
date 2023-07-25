<?php
namespace Modules\LeaveManagement\Repositories\Interfaces;

interface LeaveRepositoryInterface
{
    /**
     * leave type list
     * @return mixed
     */
    public function leave_types();

    /**
     *
     * @param $request
     * @param array $existDates
     * @return mixed
     */
    public function leave_store($request, $existDates);

    public function leave_list();

}
