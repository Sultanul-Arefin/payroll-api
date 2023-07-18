<?php
namespace Modules\Company\Http\Traits;

use Illuminate\Support\Collection;
use Modules\Company\Entities\AnnualHoliday;
use Modules\Company\Entities\WeeklyHoliday;

trait LeaveTrait{

    public function isHolidayExist(array $data)
    {
         $annualHoliday = AnnualHoliday::query()
                        ->where('company_id', auth()->user()->company_id)
                        ->whereIn('dates', $data)
                        ->pluck('dates')
                        ->all();
         $weeklyHoliday = $this->isWeeklyExist($data);
         //return $annualHoliday->concat($weeklyHoliday);
         return array_merge($annualHoliday, $weeklyHoliday);

    }
    public function isWeeklyExist(array $data)
    {
        return WeeklyHoliday::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('dates', $data)
            ->pluck('dates')
            ->all();
    }

}
