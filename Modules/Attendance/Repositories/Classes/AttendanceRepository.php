<?php

namespace Modules\Attendance\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Repositories\Interfaces\AttendanceRepositoryInterface;


class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    /**
     * Attendance Repository constructor.
     *
     * @param Attendance $model
     */
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return mixed
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): mixed
    {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model
            ::query()
            ->where('user_id', auth()->user()->id)
            ->with($relations)
            ->latest('id');
    }
}
