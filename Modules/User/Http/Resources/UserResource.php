<?php

namespace Modules\User\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class UserResource extends JsonResource
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
                    'name',
                    'email'
                ])
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user_details' => $this->user_details
        ];
    }
}
