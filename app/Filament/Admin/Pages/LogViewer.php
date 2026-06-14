<?php

namespace App\Filament\Admin\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Log Viewer';

    protected static ?string $slug = 'logs';

    protected static string $view = 'filament.admin.pages.log-viewer';

    public string $log = '';

    public string $selectedLog = 'laravel';

    public array $logFiles = [];

    public int $lines = 100;

    public float $diskUsedPercent = 0;

    public string $diskUsed = '';

    public string $diskTotal = '';

    public bool $diskWarning = false;

    public function mount(): void
    {
        $this->logFiles = $this->getLogFiles();
        $this->refresh();
        $this->checkDiskUsage();
    }

    public function refresh(): void
    {
        $path = storage_path("logs/{$this->selectedLog}.log");
        if (! File::exists($path)) {
            $this->log = 'File not found.';
            return;
        }

        $file = File::get($path);
        $lines = explode("\n", $file);
        $lines = array_slice($lines, -$this->lines);
        $this->log = implode("\n", $lines);
    }

    public function deleteSelected(): void
    {
        $path = storage_path("logs/{$this->selectedLog}.log");
        if (! File::exists($path)) {
            Notification::make()->danger()->title('File not found')->send();
            return;
        }

        File::delete($path);

        Notification::make()->success()->title("Deleted {$this->selectedLog}.log")->send();

        $this->selectedLog = 'laravel';
        $this->logFiles = $this->getLogFiles();
        $this->refresh();
        $this->checkDiskUsage();
    }

    public function clearAllLogs(): void
    {
        $files = glob(storage_path('logs/*.log'));
        $count = 0;
        foreach ($files as $file) {
            if (basename($file) === '.gitignore') {
                continue;
            }
            File::delete($file);
            $count++;
        }

        Notification::make()->success()->title("Cleared {$count} log files")->send();

        $this->selectedLog = 'laravel';
        $this->logFiles = $this->getLogFiles();
        $this->refresh();
        $this->checkDiskUsage();
    }

    public function checkDiskUsage(): void
    {
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $percent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        $this->diskUsedPercent = $percent;
        $this->diskUsed = $this->formatBytes($diskUsed);
        $this->diskTotal = $this->formatBytes($diskTotal);
        $this->diskWarning = $percent >= 85;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }

    protected function getLogFiles(): array
    {
        $files = glob(storage_path('logs/*.log'));
        $result = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $result[$name] = $name . '.log (' . round(filesize($file) / 1024, 1) . ' KB)';
        }
        return $result;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
