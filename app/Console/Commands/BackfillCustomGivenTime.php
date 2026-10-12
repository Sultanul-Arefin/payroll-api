<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackfillCustomGivenTime extends Command
{
    protected $signature = 'attendance:random-custom-time';

    protected $description = 'Fill empty custom_given_time with random time between 8:45 AM and 9:30 AM';

    public function handle()
    {
        $updated = 0;

        DB::table('attendances')
            ->whereNull('custom_given_time')
            ->whereNotNull('created_at')
            ->orderBy('id')
            ->chunkById(500, function ($attendances) use (&$updated) {
                foreach ($attendances as $attendance) {
                    $date = Carbon::parse($attendance->created_at);

                    // Random time between 09:00:00 AM and 09:30:00 AM
                    $start = 9 * 3600;
                    $end = 9 * 3600 + 30 * 60;

                    $randomSeconds = rand($start, $end);

                    $randomTime = $date->copy()->startOfDay()
                        ->addSeconds($randomSeconds);

                    DB::table('attendances')
                        ->where('id', $attendance->id)
                        ->whereNull('custom_given_time')
                        ->update([
                            'custom_given_time' => $randomTime,
                        ]);

                    $updated++;
                }
            });

        $this->info("Successfully processed {$updated} attendance records.");

        return Command::SUCCESS;
    }
}
