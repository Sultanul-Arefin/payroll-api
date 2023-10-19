<?php

namespace Modules\Role\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Role\Entities\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return apiResponse(
            data: $roles
        );
    }
}
