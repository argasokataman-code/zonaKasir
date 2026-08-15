<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnpremInstance extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function status(): string
    {
        if (! $this->last_seen_at) {
            return 'missing';
        }

        $days = (int) $this->last_seen_at->diffInDays(now());

        return match (true) {
            $days <= 2 => 'online',
            $days <= 7 => 'stale',
            default => 'offline',
        };
    }
}
