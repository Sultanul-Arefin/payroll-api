<?php

namespace Modules\User\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class UserBasicSalaryResource extends JsonResource
{
    public $user_id;

    public function __construct($resource, $user_id)
    {
        parent::__construct($resource);
        $this->user_id = $user_id;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'name',
                ])
            ),
            // 'user_id' => $this->user_id,
            'amount' => $this->employeeSalaryItem->where('employee_id', $this->user_id)->first()?->amount,
            'category' => $this->salaryItemsCategory?->name,
            'time_month_hour' => null,
            'time_per' => null,
            'govt_amount' => 0,
            'employee_amount' => 0
        ];
    }
}
