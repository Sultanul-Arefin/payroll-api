<?php

namespace App\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return LengthAwarePaginator
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): LengthAwarePaginator;

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @return Collection
     */
    public function allWithOutPagination(
        array $columns = ['*'],
        array $relations = []
    ): Collection;

    /**
     * @param $columnName
     * @param $value
     * @param array $relations
     * @param array|string[] $columns
     * @return Model|null
     */
    public function find(
        $columnName,
        $value,
        array $relations = [],
        array $columns = ['*']
    ): ?Model;

    /**
     * @param int $id
     * @param array $relations
     * @param array $columns
     * @return Model|null
     */
    public function findById(
        int $id,
        array $relations = [],
        array $columns = ['*']
    ): ?Model;

    /**
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model;

    /**
     * @param int $id
     * @param array $attributes
     * @return mixed
     */
    public function update(int $id, array $attributes);

    /**
     * @param int $id
     * @return int
     */
    public function delete(int $id): int;

    /**
     * @param array $columns
     * @return Model|null
     */
    public function last(array $columns = ['*']): ?Model;

    /**
     * @param array $ids
     * @return int
     */
    public function deleteMultiple(array $ids): int;
}
