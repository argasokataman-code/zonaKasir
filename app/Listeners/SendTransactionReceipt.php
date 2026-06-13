<?php

namespace App\Listeners;

use App\Events\SellingCreated;
use App\Notifications\TransactionReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTransactionReceipt implements ShouldQueue
{
    public function handle(SellingCreated $event): void
    {
        $selling = $event->selling;
        
        // Notify the user who created the sale
        if ($selling->user) {
            $selling->user->notify(new TransactionReceipt($selling));
        }

        // Notify member if member is associated with the sale and has email
        if ($selling->member && $selling->member->email) {
            // For now only DB notification, maybe later email
            // $selling->member->notify(new TransactionReceipt($selling));
        }
    }
}
