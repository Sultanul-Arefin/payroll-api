<?php

namespace Modules\Attendance\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Carbon\Carbon;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Repositories\Interfaces\AttendanceRepositoryInterface;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    /**
     * Attendance Repository constructor.
     */
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model::query()
                ->where('user_id', auth()->user()->id)
                ->whereMonth('dates', Carbon::now()->month)
                ->with($relations)
                ->latest('id');
    }
}
