<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupportTicketResource;
use App\Models\HelpArticle;
use App\Models\HelpArticleCategory;
use App\Models\Support;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportTicketController extends Controller
{
    public function index()
    {
        $support_tickets = Support::where('created_by', auth()->user()->id)->get();
        return SupportTicketResource::collection(
            $support_tickets
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'help_article_id' => 'required',
            'subject' => 'required',
            'message' => 'required'
        ]);
        $support = DB::transaction(function() use($request){
            $support = Support::create([
                'category_id' => $request->category_id,
                'page_name' => $request->help_article_id,
                'subject' => $request->subject,
                'created_by' => auth()->user()->id,
                'status' => Support::PENDING
            ]);
            $support_ticket = SupportMessage::create([
                'support_id' => $support->id,
                'user_id' => auth()->user()->id,
                'messages' => $request->message
            ]);
            return $support;
        });
        return apiResponse(
            data: $support
        );
    }

    public function help_article_category()
    {
        $help_article_category = HelpArticleCategory::get();
        return apiResponse(
            data: $help_article_category
        );
    }

    public function help_articles(Request $request)
    {
        $request->validate([
            'help_article_category_id' => 'required'
        ]);
        $help_article = HelpArticle::where('article_category_id', $request->help_article_category_id)->get();
        return apiResponse(
            data: $help_article
        );
    }
}
