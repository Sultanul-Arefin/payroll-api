<?php

namespace App\Http\Controllers;

use App\Http\Resources\HelpArticleCategoryResource;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use Illuminate\Http\Request;

class HelpArticleController extends Controller
{
    public function help_article_category()
    {
        $data = HelpArticleCategory::get();
        return HelpArticleCategoryResource::collection(
            $data
        );
    }

    public function help_article(HelpArticle $help_article)
    {
        return apiResponse(
            data: $help_article
        );
    }
}
