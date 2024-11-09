<?php

namespace App\Http\Controllers;

use App\Models\YoutubeVideo;
use Illuminate\Http\Request;

class YoutubeVideoController extends Controller
{
    public function index()
    {
        $youtube_videos = YoutubeVideo::get();

        if(count($youtube_videos) > 0){
            return apiResponse(
                data: $youtube_videos
            );
        }
        return apiResponse(
            data: null
        );
    }
}
