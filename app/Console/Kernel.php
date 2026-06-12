<?php

namespace App\Console;

use App\Console\Commands\CheckBilling;
use App\Console\Commands\DeleteTempFile;
use App\Console\Commands\FCM;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DeleteTempFile::class)->daily();
        $schedule->command(FCM::class)->daily();
        $schedule->command(CheckBilling::class)->daily();
        $schedule->command(\App\Console\Commands\PaymentsReconcile::class)->dailyAt('02:00');
        $schedule->command(\App\Console\Commands\PaymentsGenerateSettlements::class)->dailyAt('03:00');
        $schedule->command(\App\Console\Commands\PaymentsRetryFailedWebhooks::class)->everyTenMinutes();
        $schedule->command(\App\Console\Commands\PaymentsCancelExpired::class)->dailyAt('08:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
