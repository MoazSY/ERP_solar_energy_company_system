<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class InputOutputRequestNotification extends Notification
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
            'notification_type'=>'input_output_request',
            'title' => $this->requestModel->request_type === 'input' ? 'طلب إدخال فاتورة' : 'طلب إخراج فاتورة',
            'message' => 'يرجى معالجة طلب ' . $this->requestModel->request_type . ' المتعلق بالفاتورة.',
            'request_id' => $this->requestModel->id ?? null,
            'invoice_id' => $this->requestModel->invoice_id ?? null,
            'order_id' => $this->requestModel->order_id ?? null,
            'request_type' => $this->requestModel->request_type ?? null,
            'status'=>$this->requestModel->status,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
