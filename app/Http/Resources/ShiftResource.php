<?php

namespace App\Http\Resources;

use App\Models\RotaShift;
use App\Models\Support;
use Carbon\CarbonPeriod;
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
                    'name'
                ])
            ),
            'dates' => $this->getShiftData(),
        ];
    }

    public function getShiftData()
    {
        $period = CarbonPeriod::create(request('start_date'), request('end_date'));
        $dates = [];
        foreach($period as $date)
        {
            $key = $date->format('Y-m-d');

            // Query shifts for that day
            $shifts = RotaShift::query()
                ->whereDate('date', $key)
                ->where('employee_id', $this->id)
                ->select('start_time', 'end_time', 'break', 'notes', 'sent_notification', 'published')
                ->get()
                ->toArray();
            
            $dates[] = [
                $key => $shifts
            ];
        }
        return $dates;
    }
}
