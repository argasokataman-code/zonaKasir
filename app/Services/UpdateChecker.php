<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class UpdateChecker
{
    private string $url;

    public function __construct()
    {
        $this->url = config('updater.url');
    }

    public function getCurrentVersion(): string
    {
        $versionPath = base_path('version.txt');

        if (! File::exists($versionPath)) {
            return 'Development';
        }

        $version = trim((string) File::get($versionPath));

        return $version !== '' ? $version : 'Development';
    }

    private function fetchAndCacheApiResponse(): ?array
    {
        try {
            $response = Http::timeout(5)->get($this->url);

            if (! $response->ok()) {
                return null;
            }

            return cache()->remember('api_response', now()->addMinutes(60 * 8), fn () => $response->json());
        } catch (ConnectionException) {
            return null;
        }
    }

    public function getLatestVersion(): ?string
    {
        $response = $this->fetchAndCacheApiResponse();

        return $response ? ltrim($response['tag_name'], 'v') : null;
    }

    public function isUpdateAvailable(): bool
    {
        $current = $this->getCurrentVersion();
        $latest = $this->getLatestVersion();

        return $latest && version_compare($latest, $current, '>');
    }

    public function getChangelog(): ?string
    {
        $response = $this->fetchAndCacheApiResponse();

        return $response ? $response['body'] : null;
    }

    public function getChangelogLines(): array
    {
        $response = $this->fetchAndCacheApiResponse();

        if (! $response) {
            return [];
        }

        $body = $response['body'];

        return array_filter(preg_split('/\r\n|\r|\n/', $body));
    }
}
