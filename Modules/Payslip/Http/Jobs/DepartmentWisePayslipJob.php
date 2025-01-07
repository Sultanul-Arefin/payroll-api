<?php

namespace Modules\Payslip\Http\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Modules\Payslip\Http\Controllers\PayslipController;
use Modules\User\Emails\SendPassword;

class DepartmentWisePayslipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $auth_user;
    public $department_id;
    public $from_date;
    public $to_date;
    public $payment_date;

    /**
     * Create a new message instance.
     */
    public function __construct($auth_user, $department_id, $from_date, $to_date, $payment_date)
    {
        $this->auth_user = $auth_user;
        $this->department_id = $department_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->payment_date = $payment_date;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mail::to($this->email)
        //     ->send(new SendPassword($this->username, $this->password));
        $users = User::where('department_id', $this->department_id)->where('company_id', $this->auth_user->company_id)->get();
        foreach($users as $user){
            $request = new Request([
                'employee_id' => $user->id,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
                'payment_date' => $this->payment_date,
                'company_id' => $this->auth_user->company_id
            ]);
            app(PayslipController::class)->run_payslip($request);
            // dump($user->id);
        }
        // dump($this->auth_user->company_id);
        // dump([1,2,3,4]);
    }
}
