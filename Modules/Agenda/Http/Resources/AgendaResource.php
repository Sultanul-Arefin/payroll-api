<?php

namespace Modules\Agenda\Http\Resources;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class AgendaResource extends JsonResource
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
                    'title',
                    'description',
                    'start_date',
                    'end_date'
                ])
            ),
            'start_date_formatted' => $this->convert_date($this->start_date),
            'end_date_formatted' => $this->convert_date($this->end_date)
        ];
    }

    function convert_date($given_date) {
        // Create a DateTime object from the original date string
        $originalDateTime = new DateTime($given_date);

        // Format the DateTime object to the desired format
        $newDateString = $originalDateTime->format('Y-m-d\TH:i:s');
        return $newDateString;
    }
}
