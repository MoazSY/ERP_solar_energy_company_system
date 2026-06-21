<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ConflictInvoiceNotification extends Notification
{
    use Queueable;

    protected $conflict;

    public function __construct($conflict)
    {
        $this->conflict = $conflict;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type'=>'invoice_conflict',
            'title' => 'تم رفع تعارض على فاتورة',
            'message' => $this->conflict->conflict_description ?? null,
            'agency_id'=>$this->conflict->agency_id,
            'conflict_type'=>$this->conflict->conflict_type,
            'conflict_id' => $this->conflict->id,
            'invoice_id' => $this->conflict->invoice_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
