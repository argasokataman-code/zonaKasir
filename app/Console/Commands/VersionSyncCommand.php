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

        $tag = $this->getLatestTag();

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

    private function getLatestTag(): string
    {
        // Try git describe via exec()
        if (\function_exists('exec')) {
            @\exec('git describe --tags --abbrev=0 2>/dev/null', $output, $exitCode);
            if ($exitCode === 0 && ! empty($output[0])) {
                return \trim($output[0]);
            }
        }

        // Fallback: read tags from filesystem
        $tagsDir = base_path('.git/refs/tags');
        if (! is_dir($tagsDir)) {
            return '';
        }

        $tags = \glob($tagsDir.'/*');
        if (empty($tags)) {
            return '';
        }

        \usort($tags, function ($a, $b) {
            return \filemtime($b) - \filemtime($a);
        });

        return \pathinfo($tags[0], \PATHINFO_FILENAME);
    }
}
