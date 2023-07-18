<?php

namespace Modules\Company\Http\Services;
use DateTime;
use DateInterval;
use DatePeriod;


class CompanyService{

    public function yearlyWeekendDays($requestDays){

        $week_days = array();
        $year = date('Y');

        // Set the initial date, & end date of the given year
        $start_date = new DateTime($year . '-01-01');
        $end_date = new DateTime($year . '-12-31');

        // defining the date interval of one day
        $interval = new DateInterval('P1D');

        // creating the date range
        $date_range = new DatePeriod($start_date, $interval, $end_date);

        foreach($date_range as $date){
            // here friday in the index 5
            foreach($requestDays as $days){
                if($date->format('N') == $days){
                    $week_days[] = $date->format('Y-m-d');
                }
            }
        }
        return $week_days;
    }
}
