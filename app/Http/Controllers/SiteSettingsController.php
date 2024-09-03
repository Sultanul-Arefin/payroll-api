<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function index()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'http://admin.payroll.tellpe.com/api/v1/site-settings-data',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);


        // Check if the response is valid JSON
        if ($response !== false) {
            // Decode the JSON response into a PHP array
            $data = json_decode($response, true);

            return $this->apiResponse(
                data: $data
            );
        } else {
            return $this->apiResponse(
                data: null
            );
        }
    }
}
