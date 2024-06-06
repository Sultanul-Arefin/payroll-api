<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffObjectiveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'review_data' => date('d-m-Y', strtotime($this->review_date)),
            'employee_name' => $this->user?->name,
            'review_bye' => $this->reviewBy?->name,
            'meeting_details' => $this->meeting_details,
            'review_notes' => $this->review_notes,
            'official_review_notes' => $this->official_review_notes,
            'review_document' => $this->review_document,
        ];
    }
}
