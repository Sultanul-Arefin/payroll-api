<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    $site_settings = SiteSetting::first();
    $site_settings->logo = $site_settings->changed_logo;
    $site_settings->loader = $site_settings->changed_loader;
    $site_settings->favicon = $site_settings->changed_favicon;
    return $this->apiResponse(
        data: $site_settings
    );
}
