<?php

namespace Modules\ProjectManagement\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\ProjectManagement\Entities\Project;
use Modules\ProjectManagement\Repositories\Interfaces\ProjectInterface;

class ProjectRepository extends BaseRepository implements ProjectInterface
{
    /**
     * Project Repository constructor.
     */
    public function __construct(Project $model)
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
        if(auth()->user()->role_id == User::ADMIN)
        {
            return $this->model::query()
                ->when(!is_null(request('status')), function ($query) {
                    $query->where(
                        'is_completed',
                        request('status')
                    );
                })
                ->where('company_id', auth()->user()->company_id)
                ->with($relations)
                ->latest('id');
        }
        return $this->model::query()
                ->when(!is_null(request('status')), function ($query) {
                    $query->where(
                        'is_completed',
                        request('status')
                    );
                })
                ->where(function($query){
                    $query->where('created_by', auth()->user()->id)
                            ->orWhere(function($query){
                                $query->whereNotNull('project_manager')
                                        ->where('project_manager', auth()->user()->id);
                            })
                            ->orWhere(function($query){
                                $query->whereHas(
                                    'tasks', function(Builder $builder){
                                        $builder->whereHas(
                                            'associated_users', function(Builder $builder){
                                                $builder->where('user_id', auth()->user()->id);
                                            }
                                        );
                                    }
                                );
                            });
                })
                ->where('company_id', auth()->user()->company_id)
                ->with($relations)
                ->latest('id');
    }
}
