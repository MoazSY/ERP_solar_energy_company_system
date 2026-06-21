<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $taskType;

    public function __construct($task, string $taskType = 'delivery')
    {
        $this->task = $task;
        $this->taskType = $taskType;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $title = $this->taskType === 'delivery' ? 'مهمة توصيل مُسندة إليك' : 'مهمة فنية مُسندة إليك';
        $message = $this->taskType === 'delivery'
            ? 'تم اسناد مهمة توصيل جديدة. تحقق من تفاصيل المهمة.'
            : 'تم اسناد مهمة تركيب/فحص /كشف جديدة. تحقق من تفاصيل المهمة.';

        return [
            'notification_type'=>'task_assigned',
            'title' => $title,
            'message' => $message,
            'task_id' => $this->task->id ?? null,
            'task_type' => $this->taskType,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
