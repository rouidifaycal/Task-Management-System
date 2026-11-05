<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $taskId,
        public string $taskTitle,
        public string $projectName,
        public string $assignedByName,
        public string $taskUrl,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('Task assigned to you'),
            'task_id' => $this->taskId,
            'task_title' => $this->taskTitle,
            'project_name' => $this->projectName,
            'assigned_by' => $this->assignedByName,
            'url' => $this->taskUrl,
        ];
    }
}
