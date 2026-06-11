<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'System Health';

    protected static ?string $slug = 'system-health';

    protected static string $view = 'filament.admin.pages.system-health';

    public array $checks = [];

    public function mount(): void
    {
        $this->checks = $this->runChecks();
    }

    protected function runChecks(): array
    {
        $phpVersion = PHP_VERSION;
        $phpVersionOk = version_compare($phpVersion, '8.1', '>=');

        $dbOk = false;
        $dbMessage = '';
        try {
            DB::connection()->getPdo();
            $dbOk = true;
            $dbMessage = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $dbMessage = $e->getMessage();
        }

        $diskFree = disk_free_space(base_path());
        $diskTotal = disk_total_space(base_path());
        $diskUsed = ($diskTotal - $diskFree);
        $diskPercent = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100, 1) : 0;
        $diskOk = $diskPercent < 90;

        $logSize = 0;
        $logFiles = glob(storage_path('logs/*.log'));
        foreach ($logFiles as $file) {
            $logSize += filesize($file);
        }
        $logSizeMb = round($logSize / 1024 / 1024, 1);
        $logOk = $logSizeMb < 500;

        $env = app()->environment();
        $debug = config('app.debug');

        return [
            'php' => ['label' => 'PHP Version', 'value' => $phpVersion, 'ok' => $phpVersionOk],
            'database' => ['label' => 'Database', 'value' => $dbMessage, 'ok' => $dbOk],
            'disk' => ['label' => 'Disk Usage', 'value' => "{$diskPercent}% ({$this->formatBytes($diskFree)} free)", 'ok' => $diskOk],
            'logs' => ['label' => 'Log Size', 'value' => "{$logSizeMb} MB", 'ok' => $logOk],
            'env' => ['label' => 'Environment', 'value' => $env, 'ok' => true],
            'debug' => ['label' => 'Debug Mode', 'value' => $debug ? 'Enabled' : 'Disabled', 'ok' => ! $debug],
        ];
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
