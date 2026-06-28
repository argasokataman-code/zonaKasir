<?php

namespace App\Filesystem;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class SupabaseStorageAdapter implements FilesystemAdapter
{
    private Client $http;

    private string $bucket;

    private string $projectUrl;

    private string $apiKey;

    private string $publicUrl;

    public function __construct(array $config)
    {
        $this->bucket = $config['bucket'] ?? 'uploads';
        $this->projectUrl = rtrim($config['project_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->http = new Client([
            'base_uri' => $this->projectUrl . '/storage/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);
    }

    public function fileExists(string $path): bool
    {
        try {
            $response = $this->http->head("object/{$this->bucket}/{$path}");

            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    public function directoryExists(string $path): bool
    {
        return $this->fileExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->http->post("object/{$this->bucket}/{$path}", [
            'body' => $contents,
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
        ]);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $stream = Utils::streamFor($contents);
        $this->write($path, $stream->getContents(), $config);
    }

    public function read(string $path): string
    {
        $response = $this->http->get("object/{$this->bucket}/{$path}");

        return (string) $response->getBody();
    }

    public function readStream(string $path)
    {
        $response = $this->http->get("object/{$this->bucket}/{$path}");

        return $response->getBody()->detach();
    }

    public function delete(string $path): void
    {
        $this->http->delete("object/{$this->bucket}/{$path}");
    }

    public function deleteDirectory(string $path): void
    {
        $this->http->delete("object/{$this->bucket}/{$path}");
    }

    public function createDirectory(string $path, Config $config): void
    {
    }

    public function setVisibility(string $path, string $visibility): void
    {
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, 'public');
    }

    public function mimeType(string $path): FileAttributes
    {
        try {
            $response = $this->http->head("object/{$this->bucket}/{$path}");
            $mime = $response->getHeaderLine('Content-Type') ?: 'application/octet-stream';

            return new FileAttributes($path, null, null, null, $mime);
        } catch (\Throwable) {
            return new FileAttributes($path, null, null, null, 'application/octet-stream');
        }
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            $response = $this->http->head("object/{$this->bucket}/{$path}");
            $timestamp = $response->getHeaderLine('Last-Modified') ? strtotime($response->getHeaderLine('Last-Modified')) : time();

            return new FileAttributes($path, null, null, $timestamp);
        } catch (\Throwable) {
            return new FileAttributes($path);
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            $response = $this->http->head("object/{$this->bucket}/{$path}");
            $size = (int) ($response->getHeaderLine('Content-Length') ?: 0);

            return new FileAttributes($path, $size);
        } catch (\Throwable) {
            return new FileAttributes($path);
        }
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $response = $this->http->post("object/list/{$this->bucket}", [
            'json' => [
                'prefix' => $path,
                'limit' => 1000,
                'offset' => 0,
            ],
        ]);

        $files = json_decode((string) $response->getBody(), true) ?? [];

        foreach ($files as $file) {
            yield new FileAttributes(
                $file['name'],
                $file['metadata']['size'] ?? null,
                'public',
                strtotime($file['updated_at'] ?? 'now') ?: null,
                $file['metadata']['mimetype'] ?? null,
            );
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
        $this->delete($source);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $contents = $this->read($source);
        $this->write($destination, $contents, $config);
    }
}
