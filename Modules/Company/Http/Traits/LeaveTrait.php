<?php

namespace Modules\Company\Http\Traits;

use Modules\Company\Entities\AnnualHoliday;
use Modules\Company\Entities\WeeklyHoliday;

trait LeaveTrait
{
    /**
     * all holiday exist or not
     */
    public function isHolidayExist(array $data): ?array
    {
        //annual holiday fetch
        $annualHoliday = AnnualHoliday::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('dates', $data)
            ->pluck('dates')
            ->all();
        $weeklyHoliday = $this->isWeeklyExist($data);
        //return $annualHoliday->concat($weeklyHoliday);
        return array_merge($annualHoliday, $weeklyHoliday);

    }

    /**
     * weekly holiday exist or not
     */
    public function isWeeklyExist(array $data): ?array
    {
        return WeeklyHoliday::query()
            ->where('company_id', auth()->user()->company_id)
            ->whereIn('dates', $data)
            ->pluck('dates')
            ->all();
    }
}
