<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ReportCreatedNotification extends Notification
{
    use Queueable;

    protected $report;

    public function __construct($report)
    {
        $this->report = $report;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type'=>'report_created',
            'title' => 'تم استلام بلاغ جديد',
            'message' => $this->report->report_subject ?? null,
            'report_id' => $this->report->id,
            'company_id' => $this->report->company_id,
            'customer_id' => $this->report->customer_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
