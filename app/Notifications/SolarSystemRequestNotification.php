<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

use Illuminate\Notifications\Notification;

class SolarSystemRequestNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $request_solar_system;
    public function __construct($request_solar_system)
    {
        $this->request_solar_system = $request_solar_system;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database','broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {        return [
            'notification_type'=>'solar_system_request',
            'title' => 'تم تقديم طلب نظام شمسي جديد',
            'message' => 'تم تقديم طلب نظام شمسي جديد من قبل عميل',
            'request_id' => $this->request_solar_system->id,
            'customer_id' => $this->request_solar_system->customer_id,
        ];
        }
        public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
