<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BroadcastMessage extends Notification
{
    use Queueable;

    public function __construct(public string $subject, public string $body)
    {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->subject,
            'body' => $this->body,
            'format' => 'filament',
            'duration' => 'persistent',
        ];
    }
}
