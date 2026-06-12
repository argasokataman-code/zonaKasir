<?php

namespace App\Filament\Admin\Pages;

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

    public function mount(): void
    {
        $this->logFiles = $this->getLogFiles();
        $this->refresh();
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
