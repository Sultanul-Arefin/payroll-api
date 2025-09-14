<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceDetail;

class UpdateAttendanceForYeasinRahamanBrian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-attendance-for-yeasin-rahaman-brian';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Attendance For Yeasin Rahaman Brian';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayMemoryUses('Starting Migration');
        $this->alert('Migration Started');

        $this->clean_data();

        $this->start_populating();

        $this->cleanUpMemory();
        $this->alert('Migration Ended');
        $this->displayMemoryUses('Ended Migration');
    }

    public function cleanUpMemory()
    {
        gc_collect_cycles();
    }

    public function displayMemoryUses($title)
    {
        $size = memory_get_usage(true);
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        $result =
            @round($size / pow(1024, $i = floor(log($size, 1024))), 2) .
            ' ' .
            $unit[$i];
        $this->info('Memory Uses: ' . $title . ' : ' . $result);
    }

    public function clean_data()
    {
        $this->alert('Started cleaning data');

        $attendance = Attendance::query()
            ->whereIn('user_id', [50,52,170])
            ->whereBetween('dates', ['2024-01-01', '2024-12-31'])
            ->delete();

        $this->alert('Ended clening data');
    }

    public function start_populating()
    {
        $this->alert("Started The Data Migration");

        $users = User::query()
            ->whereIn('id', [50, 52, 170])
            ->get();

        foreach ($users as $user) {
            $mirza = User::where('id', 51)->first();
            $attendance_of_mirza = Attendance::query()
                                ->where('user_id', $mirza->id)
                                ->whereBetween('dates', ['2024-01-01', '2024-12-31'])
                                ->get();
            foreach($attendance_of_mirza as $mirza)
            {
                $attendance = Attendance::create([
                    'dates' => $mirza->dates,
                    'user_id' => $user->id,
                    'status' => 1,
                ]);
                
                $attendance_details = AttendanceDetail::create([
                    'attendance_id' => $attendance->id,
                    'office_type' => AttendanceDetail::FROM_OFFICE,
                    'in_time' => '09:00:00',
                    'out_time' => '18:30:00'
                ]);
            }
        }

        $this->alert("Completed The Data Migration");
    }
}
