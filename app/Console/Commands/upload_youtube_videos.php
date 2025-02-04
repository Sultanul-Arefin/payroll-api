<?php

namespace App\Console\Commands;

use App\Models\YoutubeVideo;
use Illuminate\Console\Command;

class upload_youtube_videos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:upload_youtube_videos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will add youtube videos in youtube_videos table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayMemoryUses('Starting Migration');
        $this->alert('Migration Started');

        $this->cleanUpYoutubeVideosTable();

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

    public function cleanUpYoutubeVideosTable()
    {
        $this->alert("Started Cleaning Up The Youtube Videos Table");

        YoutubeVideo::query()
            ->delete();

        $this->alert("Completed Cleaning Up The Youtube Videos Table");
    }

    public function start_populating()
    {
        $this->alert("Started The Data Migration");

        $videos = [
            "omada" => [
                [
                    "Company Setup and Payslip Setup | Omada Payroll",
                    "company_setup_and_payslip_setup",
                    "https://www.youtube.com/watch?v=LzfHBzU6SdA&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=1&pp=iAQB",
                    null
                ],
                [
                    "Setup Your Employee Profile | Omada Payroll",
                    "employee_profile",
                    "https://www.youtube.com/watch?v=aHxD4ygla9U&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=2&pp=iAQB",
                    null
                ],
                [
                    "How to Create Payslip Items for Employee | Omada Payroll",
                    "payslip_items_for_employee",
                    "https://www.youtube.com/watch?v=MdguUYs2cxY&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=5&pp=iAQB",
                    null
                ],
                [
                    "How to create a Payslip for an Employee | Omada HR Payroll",
                    "create_payslip",
                    "https://www.youtube.com/watch?v=8A0Qo58AHHM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=4&pp=iAQB",
                    null
                ],
                [
                    "How to create Payslip Items for all Services | Omada HR Payroll",
                    "payslip_items_for_all_services",
                    "https://www.youtube.com/watch?v=MdguUYs2cxY&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=5&pp=iAQB",
                    null
                ],
                [
                    "How to create a Unique Dashboard for Each Member | Omada HR Payroll",
                    "dashboard",
                    "https://www.youtube.com/watch?v=sHqlt-p7Fpk&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=6&pp=iAQB",
                    null
                ],
                [
                    "HR Manager Dashboard Panel | Omada Payroll",
                    "hr_manager_dashboard_panel",
                    "https://www.youtube.com/watch?v=yp5DpyutRT0&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=7&pp=iAQB",
                    null
                ],

                [
                    "Line Manager Dashboard Panel | Omada Payroll",
                    "line_manager_dashboard_panel",
                    "https://www.youtube.com/watch?v=6byGL_q88VM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=8&pp=iAQB",
                    null
                ],
                [
                    "Overview of a Staff Dashboard | Omada Payroll",
                    "staff_dashboard",
                    "https://www.youtube.com/watch?v=65wueAkEJdM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=9&pp=iAQB",
                    null
                ],
                [
                    "Multi Language Option | Omada Payroll",
                    "multi_language",
                    "https://www.youtube.com/watch?v=rjarqKFpDNc&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=10&pp=iAQB",
                    null
                ],
                [
                    "Overview of Task Management | Omada Payroll",
                    "task_management",
                    "https://www.youtube.com/watch?v=BZOaRRAaIwk&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=11&pp=iAQB",
                    null
                ]
            ],
            "white_label" => [
                [
                    "Company Setup and Payslip Setup | HR Payroll",
                    "company_setup_and_payslip_setup",
                    "https://www.youtube.com/watch?v=LzfHBzU6SdA&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=1&pp=iAQB",
                    1
                ],
                [
                    "Setup Your Employee Profile | HR Payroll",
                    "employee_profile",
                    "https://www.youtube.com/watch?v=aHxD4ygla9U&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=2&pp=iAQB",
                    1
                ],
                [
                    "How to Create Payslip Items for Employee | HR Payroll",
                    "payslip_items_for_employee",
                    "https://www.youtube.com/watch?v=MdguUYs2cxY&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=5&pp=iAQB",
                    1
                ],
                [
                    "How to create a Payslip for an Employee | HR HR Payroll",
                    "create_payslip",
                    "https://www.youtube.com/watch?v=8A0Qo58AHHM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=4&pp=iAQB",
                    1
                ],
                [
                    "How to create Payslip Items for all Services | HR HR Payroll",
                    "payslip_items_for_all_services",
                    "https://www.youtube.com/watch?v=MdguUYs2cxY&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=5&pp=iAQB",
                    1
                ],
                [
                    "How to create a Unique Dashboard for Each Member | HR HR Payroll",
                    "dashboard",
                    "https://www.youtube.com/watch?v=sHqlt-p7Fpk&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=6&pp=iAQB",
                    1
                ],
                [
                    "HR Manager Dashboard Panel | HR Payroll",
                    "hr_manager_dashboard_panel",
                    "https://www.youtube.com/watch?v=yp5DpyutRT0&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=7&pp=iAQB",
                    1
                ],

                [
                    "Line Manager Dashboard Panel | HR Payroll",
                    "line_manager_dashboard_panel",
                    "https://www.youtube.com/watch?v=6byGL_q88VM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=8&pp=iAQB",
                    1
                ],
                [
                    "Overview of a Staff Dashboard | HR Payroll",
                    "staff_dashboard",
                    "https://www.youtube.com/watch?v=65wueAkEJdM&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=9&pp=iAQB",
                    1
                ],
                [
                    "Multi Language Option | HR Payroll",
                    "multi_language",
                    "https://www.youtube.com/watch?v=rjarqKFpDNc&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=10&pp=iAQB",
                    1
                ],
                [
                    "Overview of Task Management | HR Payroll",
                    "task_management",
                    "https://www.youtube.com/watch?v=BZOaRRAaIwk&list=PLYTfDVksLwOBEhey_cPfF1ZgyWTaWHkNL&index=11&pp=iAQB",
                    1
                ]
            ]
        ];
        // FOR OMADA
        foreach($videos['omada'] as $value){
            YoutubeVideo::create([
                'page_title' => $value[0],
                'page_slug' => $value[1],
                'video_link' => $value[2],
                'white_label' => $value[3]
            ]);
        }

        // FOR WHITE LABEL
        foreach($videos['white_label'] as $value){
            YoutubeVideo::create([
                'page_title' => $value[0],
                'page_slug' => $value[1],
                'video_link' => $value[2],
                'white_label' => $value[3]
            ]);
        }

        $this->alert("Completed The Data Migration");
    }
}
