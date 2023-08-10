<?php

namespace Modules\Permission\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Permission\Entities\Permission;
use Modules\Permission\Http\Resources\PermissionResource;
use stdClass;

class PermissionController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function userPermissions($id=null)
    {
        // $response = {
        //     "data": {
        //         "permissions": [
        //             {
        //                 "id": "dashboard",
        //                 "children": [
        //                     {
        //                         "id": "dashboard.index"
        //                     }
        //                 ]
        //             },
        //             {
        //                 "id": "configuration",
        //                 "children": [
        //                     {
        //                         "id": "company.index"
        //                     },
        //                     {
        //                         "id": "department.index"
        //                     },
        //                     {
        //                         "id": "designation.index"
        //                     }
        //                 ]
        //             }
        //         ]
        //     }
        // };
        $response = [
            [
                "id" => "dashboard",
                "childeren" => [
                    [
                        "id" => "dashboard"
                    ]
                ]
            ],
            [
                "id" => "configuration",
                "childeren" => [
                    [
                        "id" => "company"
                    ],
                    [
                        "id" => "department"
                    ],
                    [
                        "id" => "designation"
                    ]
                ]
            ]
        ];

        if(empty($id)){
            $id = auth()->user()->id;
        }

        if(!($user = User::where('id', $id)->first())){
            return apiResponse([], 'User Not Found', 'error', 404);
        }

        $permissions = $user->getAllPermissions();
        $filteringPermission = []; //only parent data store in array
        foreach($permissions as $permission){
            if($permission->parent_id === null){
                array_push($filteringPermission, $permission);
            }
        }
        $data = PermissionResource::collection($filteringPermission);//return parent with all children under e parent
        return apiResponse($data, 'successfully has been fetch permission data', 200);

        $result_object = new stdClass();
        foreach($permissions as $value){
            if($value->parent_id === NULL){
                $permis = Permission::select('name')->where('parent_id', $value->id)->get();
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
