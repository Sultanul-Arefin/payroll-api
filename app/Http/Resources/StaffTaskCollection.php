<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class StaffTaskCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return $this->collection->map(function ($staffTask) {
            return new StaffTaskResource($staffTask);
        });
    }
}
