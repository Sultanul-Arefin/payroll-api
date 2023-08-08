<?php

namespace Modules\Dashboard\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class HolidayResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'date' => $this->dates,
            'holiday_type' => $this->holiday_type
        ];
    }
}
