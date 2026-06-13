<?php

namespace App\Notifications;

use App\Models\Tenants\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification
{
    use Queueable;

    public function __construct(public Member $member)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'New Member Registered',
            'message' => "Member {$this->member->name} ({$this->member->code}) has registered.",
            'member_id' => $this->member->id,
            'member_code' => $this->member->code,
            'member_name' => $this->member->name,
            'member_phone' => $this->member->phone,
            'created_at' => $this->member->created_at->toISOString(),
        ];
    }
}
