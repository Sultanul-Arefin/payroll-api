<?php

namespace App\Repositories\RepositoryClasses;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param  array|string[]  $columns
     */
    public function all(
        array $columns = ['*'],
        array $relations = [],
        int $count = 15
    ): LengthAwarePaginator {
        return $this->model::query()
                ->with($relations)
                ->latest()
                ->paginate($count, $columns);
    }

    public function allWithOutPagination(
        array $columns = ['*'],
        array $relations = []
    ): Collection {
        return $this->model::query()
                ->with($relations)
                ->get($columns);
    }

    public function create(array $attributes): Model
    {
        return $this->model::query()->create($attributes);
    }

    public function update(int $id, array $attributes): bool
    {
        return $this->model::query()->find($id)?->update($attributes);
    }

    public function delete(int $id): int
    {
        return $this->model::query()->find($id)?->delete();
    }

    public function deleteMultiple(array $ids): int
    {
        return $this->model::query()
                ->whereIn('id', $ids)
                ->delete();
    }

    /**
     * @param  array|string[]  $columns
     */
    public function findById(
        int $id,
        array $relations = [],
        array $columns = ['*']
    ): ?Model {
        return $this->model::query()
                ->select($columns)
                ->with($relations)
                ->find($id);
    }

    /**
     * @param  array|string[]  $columns
     */
    public function find(
        $columnName,
        $value,
        array $relations = [],
        array $columns = ['*']
    ): ?Model {
        return $this->model::query()
                ->select($columns)
                ->with($relations)
                ->where($columnName, $value)
                ->first();
    }

    public function last(array $columns = ['*']): ?Model
    {
        return $this->model::query()
                ->orderBy('id', 'DESC')
                ->first($columns);
    }
}
