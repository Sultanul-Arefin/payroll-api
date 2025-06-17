<?php

namespace App\Http\Resources;

use App\Models\RotaShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          'date' => $this->date,
          'total_hour' => $this->total_hour($this->date),
          'total_amount' => $this->total_amount($this->date)
        ];
    }

    private function total_hour($date)
    {
        $shifts = RotaShift::query()
                ->where('date', $date)
                ->get();

        $totalMinutes = 0;
        foreach($shifts as $shift)
        {
            $start = Carbon::createFromFormat('H:i', $shift->start_time);
            $end   = Carbon::createFromFormat('H:i', $shift->end_time);

            $totalMinutes += $start->diffInMinutes($end);
        }
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        return "{$totalHours}Hours {$remainingMinutes}Minutes";
    }

    private function total_amount($date)
    {
        return null;
    }
}
