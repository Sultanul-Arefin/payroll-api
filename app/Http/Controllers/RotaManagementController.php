<?php

namespace App\Http\Controllers;

use App\Http\Traits\RotaLocation;
use App\Http\Traits\RotaWorkSchedule;
use Illuminate\Http\Request;

class RotaManagementController extends Controller
{
    use RotaLocation, RotaWorkSchedule;
}
