<?php

namespace Modules\Role\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Role\Entities\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
                ->where('id', '!=', 1)
                ->get();

        return apiResponse(
            data: $roles
        );
    }
}
