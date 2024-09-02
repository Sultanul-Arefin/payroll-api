<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function index()
    {
        $site_settings = SiteSetting::first();
        if(!$site_settings){
            return $this->apiResponse(
                data: null
            );
        }
        return $this->apiResponse(
            data: $site_settings
        );
    }
}
