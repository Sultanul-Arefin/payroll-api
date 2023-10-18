<?php

namespace Modules\Payslip\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PayslipCreatedNotificationToUser extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public $created_by, public $payslip)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', 'https://laravel.com')
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title'         => 'Payslip Created',
            'description'   => 'Payslip for month ' . $this->payslip->month . ' is created',
            'action_by'     => [
                'name'  => $this->created_by->name,
                'email' => $this->created_by->email,
                'phone' => $this->created_by->phone
            ],
            'action_at'     => date('Y-m-d H:i:s'),
            'type'          => 'Payslip',
            'color'         => ''
        ];
    }
}
