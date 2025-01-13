<?php

namespace App\Http\Controllers;

use App\Models\YoutubeVideo;
use Illuminate\Http\Request;

class YoutubeVideoController extends Controller
{
    public function index()
    {
        $youtube_videos = YoutubeVideo::query()
                        ->when(! is_null(request('white_label')), function ($query) {
                            $query->where(
                                'white_label',
                                1
                            );
                        })
                        ->when(is_null(request('white_label')), function ($query) {
                            $query->where(
                                'white_label',
                                null
                            );
                        })
                        ->get();

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
