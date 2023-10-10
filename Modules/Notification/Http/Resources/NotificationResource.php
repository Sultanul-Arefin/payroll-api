<?php

namespace Modules\Notification\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::except(parent::toArray($request), [
                    'created_at',
                    'updated_at',
                    'notifiable_type',
                    'type'
                ])
            ),
        ];
    }
}
