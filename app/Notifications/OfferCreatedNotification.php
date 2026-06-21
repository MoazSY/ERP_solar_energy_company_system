<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class OfferCreatedNotification extends Notification
{
    use Queueable;

    protected $offer;

    public function __construct($offer)
    {
        $this->offer = $offer;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type'=>'offer_created',
            'title' => 'تم إنشاء عرض جديد',
            'message' => 'تم إنشاء عرض جديد من شركة',
            'offer_id' => $this->offer->id,
            'company_id' => $this->offer->company_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
