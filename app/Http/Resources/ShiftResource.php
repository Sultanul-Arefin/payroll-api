<?php

namespace App\Http\Resources;

use App\Models\Support;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class ShiftResource extends JsonResource
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
                ])
            ),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'dates' => [
                [
                    '18-05-25' => [
                        [
                            'shift_time' => 1,
                            'published' => 1
                        ],
                        [
                            'shift_time' => 2,
                            'published' => 2
                        ]
                    ],
                    '19-05-25' => [
                        [
                            'shift_time' => 1,
                            'published' => 1
                        ]
                    ]
                ]
            ]
        ];
    }
    

}
