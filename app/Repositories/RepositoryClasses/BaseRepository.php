<?php

namespace App\Repositories\RepositoryClasses;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

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
    ): LengthAwarePaginator {
        return $this->model
            ::query()
            ->with($relations)
            ->latest()
            ->paginate($count, $columns);
    }

    /**
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function allWithOutPagination(
        array $columns = ['*'],
        array $relations = []
    ): Collection {
        return $this->model
            ::query()
            ->with($relations)
            ->get($columns);
    }

    /**
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes): Model
    {
        return $this->model::query()->create($attributes);
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return bool
     */
    public function update(int $id, array $attributes): bool
    {
        return $this->model::query()->find($id)?->update($attributes);
    }

    /**
     * @param int $id
     * @return int
     */
    public function delete(int $id): int
    {
        return $this->model::query()->find($id)?->delete();
    }

    /**
     * @param array $ids
     * @return int
     */
    public function deleteMultiple(array $ids): int
    {
        return $this->model
            ::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * @param int $id
     * @param array $relations
     * @param array|string[] $columns
     * @return Model|null
     */
    public function findById(
        int $id,
        array $relations = [],
        array $columns = ['*']
    ): ?Model {
        return $this->model
            ::query()
            ->select($columns)
            ->with($relations)
            ->find($id);
    }

    /**
     * @param $value
     * @param $columnName
     * @param array $relations
     * @param array|string[] $columns
     * @return Model|null
     */
    public function find(
        $columnName,
        $value,
        array $relations = [],
        array $columns = ['*']
    ): ?Model {
        return $this->model
            ::query()
            ->select($columns)
            ->with($relations)
            ->where($columnName, $value)
            ->first();
    }

    /**
     * @param array $columns
     * @return Model|null
     */
    public function last(array $columns = ['*']): ?Model
    {
        return $this->model
            ::query()
            ->orderBy('id', 'DESC')
            ->first($columns);
    }
}