<?php

namespace Modules\User\Repositories\Classes;

use App\Models\User;
use App\Repositories\RepositoryClasses\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Mockery\Exception;
use Modules\Employee\Entities\Employee;
use Modules\User\Entities\UserAttachment;
use Modules\User\Entities\UserDetails;
use Modules\User\Http\Requests\UserStoreRequest;
use Modules\User\Repositories\Interfaces\UserRepositoryInterface;
use App\Exceptions\CustomException;
use App\Http\Traits\Attachment;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    use Attachment;
    /**
     * User Repository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array|string[] $columns
     * @param array $relations
     * @param int $count
     * @return CursorPaginator
     */
    public function allWithSearch(
        array $columns = ['*'],
        array $relations = [],
        int   $count = 15
    ): CursorPaginator
    {
        return $this->searchQuery($relations)->cursorPaginate($count, $columns);
    }

    private function searchQuery($relations)
    {
        return $this->model
            ::query()
            ->where('company_id', auth()->user()->company_id)
            ->when(
                !is_null(request('department_id')),
                fn(Builder $builder) => $builder->where(function ($query) {
                    $query
                        ->where(
                            'department_id',
                            request('department_id')
                        );
                })
            )
            ->when(
                !is_null(request('is_activated')) && request('is_activated') == User::USER_DISABLE,
                fn(Builder $builder) => $builder->where(function($query){
                    $query
                        ->where(
                            'status',
                            User::USER_DISABLE
                        );
                })
            )
            ->when(
                !is_null(request('is_activated')) && request('is_activated') == User::USER_ACTIVE,
                fn(Builder $builder) => $builder->where(function($query){
                    $query
                        ->where(
                            'status',
                            User::USER_ACTIVE
                        );
                })
            )
            ->when(
                is_null(request('is_activated')),
                fn(Builder $builder) => $builder->where(function($query){
                    $query
                        ->where(
                            'status',
                            User::USER_ACTIVE
                        );
                })
            )
            ->when(
                !is_null(request('search')),
                fn(Builder $builder) => $builder->where(function($query){
                    $query
                        ->where('name', 'LIKE', '%' . request('search') . '%')
                        ->orWhere('email', 'LIKE', '%' . request('search') . '%')
                        ->orWhereRelation(
                            'user_details',
                            'user_phone',
                            'LIKE',
                            '%' . request('search') . '%'
                        );
                })
            )
            ->with($relations)
            ->latest();
    }

    public function userDetailsUpdate($user_id,$attributes){
        return UserDetails::where('user_id',$user_id)?->update($attributes);
    }

    /**
     * @param object $user
     * @param int $statusTypes
     * @return bool
     */
    public function userStatus(object $user, int $statusTypes): bool
    {
        // TODO: Implement userStatus() method.
        if ($statusTypes === User::USER_ACTIVE){
            $user = $user->update([
                'status' => User::USER_ACTIVE
            ]);
        }elseif($statusTypes === User::USER_DISABLE){
            $user = $user->update([
                'status' => User::USER_DISABLE
            ]);
        }else{
            throw new CustomException("not allow employee status value", 422);
        }
        return $user;
    }
    public function userDocument($request_file_name, $heading_type, $item_type)
    {

          $data = $this->uploadAttachment($request_file_name, ('employees/'.$heading_type));
          if($data['fileName']){
              try {
                  $attach = UserAttachment::create([
                      'user_id' => auth()->user()->id,
                      'file_name' => $data['fileName'],
                      'heading_type' => UserAttachment::HEADING_TYPE[$heading_type],
                      'item_type' => $this->itemType($heading_type, $item_type)
                  ]);
              }catch (\Exception $ex){
                  $this->deleteAttachment($data['fileName']);
                  throw new CustomException('Something Wrong, Please try again', 404);
              }
           }else{
               throw new CustomException('Your File Not Accepted', 404);
           }
        return $attach;

    }
    public function multipleStoreAttachment($request, $userId)
    {

        if($request->hasFile('file_name')){
            $data = $this->uploadMultipleAttachment($request->file_name, 'contract_documents');
            if($data['fileName']){
                try {
                    foreach($data['fileName'] as $fileName){
                        $attach = UserAttachment::create([
                            'user_id' => $userId,
                            'file_name' => $fileName,
                            'heading_type' => UserAttachment::HEADING_TYPE[$request->heading_type],
                            'item_type' => $this->itemType($request->item_type, $request->heading_type)
                        ]);
                    }
                }catch (\Exception $ex){
                  //  $this->deleteMultipleAttachment($data['fileName']);
                    throw new CustomException('Something Wrong, Please try again', 404);
                }
            }else{
                throw new CustomException('Your File Not Accepted', 404);
            }
        }
        return $data['fileName'];
    }

    public function itemType($heading_type, $item_type){
        try {
            switch ($heading_type){
                case 'contract': return UserAttachment::CONTRACT_ITEM_TYPE[$item_type];
                    break;
                case 'official' : return UserAttachment::OFFICIAL_ITEM_TYPE[$item_type];
                    break;
                case 'others' : return UserAttachment::OTHERS_ITEM_TYPE[$item_type];
                    break;
                default: return false;
            }
        }catch (\Exception $ex){
            throw new CustomException('tme ting error', 404);
        }
    }
}
