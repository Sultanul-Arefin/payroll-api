<?php

namespace Modules\Permission\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Permission\Entities\Permission;
use stdClass;

class PermissionController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function userPermissions($id=null)
    {
        $response = [
            [
                "id" => "home",
                "childeren" => [
                    [
                        "id" => "home"
                    ]
                ]
            ],
            [
                "id" => "configuration",
                "childeren" => [
                    [
                        "id" => "company details"
                    ],
                    [
                        "id" => "add department"
                    ],
                    [
                        "id" => "add designation"
                    ]
                ]
            ]
        ];
        return apiResponse(
            [
                'permssions' => $response
            ]
        );
        if(empty($id)){
            $id = auth()->user()->id;
        }
        // if (!($user = $this->userRepo->findById($id))) {
        //     return $this->apiResponse([], 'User Not Found', 'error', 404);
        // }
        if(!($user = User::where('id', $id)->first())){
            return apiResponse([], 'User Not Found', 'error', 404);
        }
        $permissions = $user->getAllPermissions();
        return $permissions;
        $data = [];
        $result_object = new stdClass();
        foreach($permissions as $value){
            if($value->parent_id === NULL){
                $permis = Permission::where('parent_id', $value->id)->get();
                $permission_value = [];
                foreach($permis as $val){
                    array_push($permission_value, $val->name);
                }
                $result_object->{$value->name} = $permission_value;
            }
            else{
                $parent = Permission::where('id', $value->parent_id)->first();
                $permis = Permission::where('parent_id', $value->id)->get();

                $data = [];
                
                if(!property_exists($result_object, $parent->name)){
                    $result_object->{$parent->name} = [$value->name];
                } else{
                    if(in_array($value->name, $result_object->{$parent->name})){

                    } else{
                        array_push($result_object->{$parent->name}, $value->name);
                    }
                }
            }
        }

        // add endpoint for parent route
        $url = '';
        if(!empty($result_object)){
            foreach($result_object as $key => $value){
                $endpoint = Permission::where('name', $key)->first();
                $url = $endpoint->display_endpoint;
                if(!is_null($url)){
                    $url = $endpoint->display_endpoint;
                    break;
                }
                // array_push($result_object->{$key}, $endpoint->display_endpoint);
            }
        }
        return apiResponse(
            [
                'permissions'   => $result_object,
                'redirect_url'  => $url
            ]);
    }
}
