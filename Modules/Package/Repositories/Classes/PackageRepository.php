<?php

namespace Modules\Package\Repositories\Classes;

use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Package\Entities\Package;
use Modules\Package\Repositories\Interfaces\PackageRepositoryInterface;

class PackageRepository extends BaseRepository implements PackageRepositoryInterface
{
    /**
     * Package Repository constructor.
     */
    public function __construct(Package $model)
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
    ): CursorPaginator {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model::query()
                ->when(
                    ! is_null(request('search')),
                    fn (Builder $builder) => $builder->where(function ($query) {
                        $query
                            ->where(
                                'package_name',
                                'like',
                                '%'.request('search').'%'
                            );
                    })
                )
                ->with($relations)
                ->latest('id');
    }
}
