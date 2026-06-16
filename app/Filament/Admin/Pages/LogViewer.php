<?php

namespace App\Filament\Admin\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class LogViewer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Laravel Logs';

    protected static ?string $slug = 'laravel-logs';

    protected static string $view = 'filament.admin.pages.log-viewer';

    /** @var array<int, array{time: string, level: string, message: string, context: string, is_continuation: bool}> */
    public array $logEntries = [];

    public string $selectedLog = 'laravel';

    public array $logFiles = [];

    public int $lines = 100;

    public float $diskUsedPercent = 0;

    public string $diskUsed = '';

    public string $diskTotal = '';

    public bool $diskWarning = false;

    public int $errorCount = 0;

    public int $warningCount = 0;

    public int $totalEntries = 0;

    public string $logRaw = '';

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
            $this->logEntries = [];
            $this->logRaw = 'File not found.';
            return;
        }

        $file = File::get($path);
        $lines = explode("\n", $file);
        $lines = array_slice($lines, -$this->lines);

        $this->logEntries = $this->parseLogLines($lines);
        $this->logRaw = $this->buildRawLog($this->logEntries);

        $this->errorCount = 0;
        $this->warningCount = 0;
        $this->totalEntries = count(array_filter($this->logEntries, fn ($e) => ! $e['is_continuation']));

        foreach ($this->logEntries as $entry) {
            if ($entry['is_continuation']) {
                continue;
            }
            $level = strtoupper($entry['level']);
            if ($level === 'ERROR') {
                $this->errorCount++;
            } elseif ($level === 'WARNING') {
                $this->warningCount++;
            }
        }
    }

    public function clearAllLogs(): void
    {
        $files = glob(storage_path('logs/*.log'));
        $count = 0;
        foreach ($files as $file) {
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

    /**
     * Parse raw log lines into structured entries.
     *
     * Laravel log format: [YYYY-MM-DD HH:MM:SS] env.LEVEL: message
     * Stacktrace lines start without timestamp prefix.
     */
    private function parseLogLines(array $lines): array
    {
        $entries = [];
        $currentEntry = null;

        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(\w+):\s*(.*)$/';

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                // Save previous entry
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }
                $currentEntry = [
                    'time' => $matches[1],
                    'channel' => $matches[2],
                    'level' => $matches[3],
                    'message' => $matches[4],
                    'stack' => '',
                    'is_continuation' => false,
                ];
            } elseif ($currentEntry !== null && trim($line) !== '') {
                $currentEntry['stack'] .= $line . "\n";
            } elseif (trim($line) === '' && $currentEntry !== null) {
                // Empty line might separate entries
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        return $entries;
    }

    private function buildRawLog(array $entries): string
    {
        $output = [];
        foreach ($entries as $entry) {
            $output[] = "[{$entry['time']}] {$entry['channel']}.{$entry['level']}: {$entry['message']}";
            if ($entry['stack'] !== '') {
                $output[] = rtrim($entry['stack']);
            }
        }
        return implode("\n", $output);
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
            $size = round(filesize($file) / 1024, 1);
            $result[$name] = "{$name}.log ({$size} KB)";
        }
        return $result;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
