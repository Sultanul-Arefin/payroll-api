<?php

namespace App\Console\Commands;

use App\Models\RotaWorkSchedule;
use App\Models\User;
use App\Models\YoutubeVideo;
use Illuminate\Console\Command;

class populate_rota_work_schedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate_rota_work_schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will add data in rota_work_schedules table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayMemoryUses('Starting Migration');
        $this->alert('Migration Started');

        $this->cleanUpRotaWorkSchedulesTable();

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

    public function cleanUpRotaWorkSchedulesTable()
    {
        $this->alert("Started Cleaning Up The Rota Work Schedule Table");

        RotaWorkSchedule::query()
            ->delete();

        $this->alert("Completed Cleaning Up The Rota Work Schedule Table");
    }

    public function start_populating()
    {
        $this->alert("Started The Data Migration");

        $users = User::query()
                ->where('role_id', '!=', User::ADMIN)
                ->get();
        foreach($users as $user)
        {
            RotaWorkSchedule::create([
                'employee_id' => $user->id,
                'saturday_off' => 1,
                'sunday_off' => 0,
                'monday_off' => 0,
                'tuesday_off' => 0,
                'wednesday_off' => 0,
                'thursday_off' => 0,
                'friday_off' => 1
            ]);
        }

        $this->alert("Completed The Data Migration");
    }
}
