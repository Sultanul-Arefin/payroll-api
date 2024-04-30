<?php

namespace App\Http\Resources;

use App\Models\Support;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\Assignment\Entities\Assignment;
use Modules\AssignmentTag\Entities\Tag;
use Modules\CourseCompletionMaster\Http\Controllers\CourseCompletionMasterController;
use Modules\Enrollment\Entities\Enrollment;

class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'subject',
                    'updated_by'
                ])
            ),
            'created_by' => $this->created_by,
            'category_id' => $this->getCategory(),
            'page_name' => $this->getPage(),
            'status' => $this->getStatus($this->status),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function getStatus($status)
    {
        return match($status){
            Support::CLOSED => 'CLOSED',
            Support::ACTIVE => 'ACTIVE',
            Support::PENDING => 'PENDING',
            default => 'NO STATUS FOUND'
        };
    }

    public function getPage()
    {
        return $this?->help_article?->title;
    }

    public function getCategory()
    {
        return $this?->help_article_category?->category_name;
    }
}
