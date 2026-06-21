<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
// use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class RegistrationStatusNotification extends Notification
{
    use Queueable;

    protected $status;
    protected $entityType;
    protected $proccess_result;

    public function __construct(string $status, string $entityType = 'company', $proccess_result)
    {
        $this->status = $status;
        $this->entityType = $entityType;
        $this->proccess_result = $proccess_result;
    }

    public function via($notifiable)
    {
        // send to database and broadcast channel
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $title = $this->status === 'approved' ? 'تمت الموافقة على التسجيل' : 'تم رفض التسجيل';
        $message = $this->status === 'approved'
            ? 'حسابك تم تفعيله بواسطة الادمن.'
            : 'تم رفض طلب التسجيل؛ تواصل مع الدعم للمزيد من التفاصيل.';

        return [
            'notification_type'=>'registration_status',
            'title' => $title,
            'message' => $message,
            'status' => $this->status,
            'entity' => $this->entityType,
            'proccess_result' => $this->proccess_result,
        ];
    }

    public function toBroadcast($notifiable)
    {
                $title = $this->status === 'approved' ? 'تمت الموافقة على التسجيل' : 'تم رفض التسجيل';
        $message = $this->status === 'approved'
            ? 'حسابك تم تفعيله بواسطة الادمن.'
            : 'تم رفض طلب التسجيل؛ تواصل مع الدعم للمزيد من التفاصيل.';

        $payload = [
            'notification_type'=>'registration_status',
            'title' => $title,
            'message' => $message,
            'status' => $this->status,
            'entity' => $this->entityType,
            'proccess_result' => $this->proccess_result,
        ];

        return new BroadcastMessage($payload);
    }
}
