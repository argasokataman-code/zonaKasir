<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VersionSyncCommand extends Command
{
    protected $signature = 'app:version-sync';

    protected $description = 'Sync version.txt with the latest git tag';

    public function handle(): int
    {
        if (! is_dir(base_path('.git'))) {
            $this->warn('Git metadata is unavailable; leaving existing version.txt untouched.');

            return Command::SUCCESS;
        }

        $tag = '';
        exec('git describe --tags --abbrev=0 2>/dev/null', $output, $exitCode);
        $tag = trim($output[0] ?? '');

        if ($tag !== '') {
            file_put_contents(base_path('version.txt'), $tag);
            $this->info("version.txt synced to: {$tag}");

            return Command::SUCCESS;
        }

        $version = 'Development';
        file_put_contents(base_path('version.txt'), $version);
        $this->info("version.txt synced to: {$version}");

        return Command::SUCCESS;
    }
}
