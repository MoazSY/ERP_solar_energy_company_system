<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MaintenanceRequestNotification extends Notification
{
    use Queueable;

    protected $requestModel;

    public function __construct($requestModel)
    {
        $this->requestModel = $requestModel;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type'=>'maintenance_request',
            'title' => 'طلب صيانة جديد',
            'message' => $this->requestModel->description ?? null,
            'request_id' => $this->requestModel->id,
            'customer_id' => $this->requestModel->customer_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
