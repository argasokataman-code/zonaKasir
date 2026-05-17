<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VersionSyncCommand extends Command
{
    protected $signature = 'app:version-sync';

    protected $description = 'Sync version.txt with the latest git tag';

    public function handle(): void
    {
        $tag = trim(shell_exec('git describe --tags --abbrev=0 2>/dev/null') ?? '');

        $version = $tag ?: 'Development';

        file_put_contents(base_path('version.txt'), $version);

        $this->info("version.txt synced to: {$version}");
    }
}
