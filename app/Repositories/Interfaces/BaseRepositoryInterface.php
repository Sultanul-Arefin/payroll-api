<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * @param  array|string[]  $columns
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): LengthAwarePaginator;

    /**
     * @param  array|string[]  $columns
     */
    public function allWithOutPagination(
        array $columns = ['*'],
        array $relations = []
    ): Collection;

    /**
     * @param  array|string[]  $columns
     */
    public function find(
        $columnName,
        $value,
        array $relations = [],
        array $columns = ['*']
    ): ?Model;

    public function findById(
        int $id,
        array $relations = [],
        array $columns = ['*']
    ): ?Model;

    public function create(array $attributes): Model;

    /**
     * @return mixed
     */
    public function update(int $id, array $attributes);

    public function delete(int $id): int;

    public function last(array $columns = ['*']): ?Model;

    public function deleteMultiple(array $ids): int;
}
