<?php

namespace Modules\Company\Repositories\Classes;
use App\Repositories\RepositoryClasses\BaseRepository;
use Modules\Company\Entities\Company;
use Modules\Company\Repositories\Interfaces\CompanyRepositoryInterface;


class CompanyRepository extends BaseRepository implements CompanyRepositoryInterface
{
    /**
     * Company Repository constructor.
     *
     * @param Company $model
     */
    public function __construct(Company $model)
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
        return $this->searchQuery($relations);
    }

    private function searchQuery($relations)
    {
        return $this->model
            ::query()
            ->where('id', auth()->user()->company_id)
            ->with($relations)
            ->first();
    }
}
