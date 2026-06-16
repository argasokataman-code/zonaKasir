<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class BroadcastMessage extends Notification
{
    use Queueable;

    public function __construct(public string $subject, public string $body)
    {}

    public function via($notifiable): array
    {
        if ($notifiable->fcm_token && env('FIREBASE_CREDENTIALS')) {
            return [FcmChannel::class, 'database'];
        }

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

    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(
            notification: new FcmNotification(
                title: $this->subject,
                body: $this->body,
            )
        ))->data([
            'title' => $this->subject,
            'body' => $this->body,
        ]);
    }

    public function getType(): string
    {
        return 'broadcast_message';
    }
}
