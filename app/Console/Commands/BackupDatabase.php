<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
        {--retention= : Days to keep backups (default: config backup.retention_days)}';

    protected $description = 'Backup PostgreSQL database via pg_dump (FR-9.5)';

    public function handle(): int
    {
        $backupPath = config('backup.path', storage_path('app/backups/db'));
        $retentionDays = (int) ($this->option('retention') ?? config('backup.retention_days', 7));
        $compress = config('backup.compress', true);

        File::ensureDirectoryExists($backupPath);

        $timestamp = now()->format('Y-m-d_His');
        $filename = "zonaKasir_{$timestamp}.sql" . ($compress ? '.gz' : '');
        $fullPath = rtrim($backupPath, '/') . '/' . $filename;

        $this->info("📦 Backing up database...");

        try {
            $this->runPgDump($fullPath, $compress);
        } catch (Exception $e) {
            $this->error("❌ Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        $size = round(File::size($fullPath) / 1024, 1) . ' KB';
        $this->info("✅ Backup saved: {$filename} ({$size})");

        $this->cleanOldBackups($backupPath, $retentionDays);

        return Command::SUCCESS;
    }

    private function runPgDump(string $path, bool $compress): void
    {
        $host = config('database.connections.pgsql.host', '127.0.0.1');
        $port = config('database.connections.pgsql.port', '5432');
        $database = config('database.connections.pgsql.database', 'forge');
        $username = config('database.connections.pgsql.username', 'forge');
        $password = config('database.connections.pgsql.password', '');

        $cmd = sprintf(
            'pg_dump -h %s -p %s -U %s -d %s --no-owner --no-acl --clean --if-exists',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
        );

        if ($compress) {
            $cmd .= ' | gzip';
        }

        $cmd .= ' > ' . escapeshellarg($path);

        $output = [];
        $exitCode = 0;

        // pg_dump runs via shell to support pipe to gzip
        $envStr = 'PGPASSWORD=' . escapeshellarg($password);
        exec($envStr . " bash -c " . escapeshellarg($cmd), $output, $exitCode);

        if ($exitCode !== 0) {
            throw new Exception('pg_dump exited with code ' . $exitCode . ': ' . implode("\n", $output));
        }

        if (! file_exists($path) || filesize($path) === 0) {
            throw new Exception('Backup file is empty or not created');
        }
    }

    private function cleanOldBackups(string $path, int $retentionDays): void
    {
        $cutoff = now()->subDays($retentionDays);
        $deleted = 0;

        foreach (File::files($path) as $file) {
            if ($file->getMTime() < $cutoff->timestamp) {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        if ($deleted > 0) {
            $this->info("🗑️ Cleaned {$deleted} old backup(s) (retention: {$retentionDays} days)");
        }
    }
}
