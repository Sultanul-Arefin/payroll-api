<?php

namespace Modules\Company\Repositories\Interfaces;

interface CompanyRepositoryInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): mixed;
}
