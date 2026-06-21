<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ReportProcessedNotification extends Notification
{
    use Queueable;

    protected $process;

    public function __construct($process)
    {
        $this->process = $process;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'notification_type'=>'report_proccess',
            'title' => 'تم معالجة بلاغ على شركتك بواسطة الادمن',
            'message' => $this->process->notes ?? null,
            'report_id' => $this->process->report_id,
            'admin_id' => $this->process->admin_id,
            'proccess_method' => $this->process->proccess_method,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
