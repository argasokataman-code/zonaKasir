<?php

namespace App\Filament\Tenant\Resources\Traits;

use App\Models\Tenants\UploadedFile;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;

trait HasUploadFileField
{
    /**
     * Support both string (relative path) and array payloads that Filament may pass.
     * Filament sometimes provides an array with keys like `name`, `url`, `size`, `type`.
     */
    private function getUploadedFileUsing(BaseFileUpload $component, string|array $file, string|array|null $storedFileNames)
    {
        /** @var Storage $storage */
        $storage = $component->getDisk();

        $shouldFetchFileInformation = $component->shouldFetchFileInformation();

        // If Filament provided an array payload, normalize it
        if (is_array($file)) {
            $name = $file['name'] ?? $file['relative_path'] ?? null;
            $url = $file['url'] ?? null;
            $size = $file['size'] ?? 0;
            $type = $file['type'] ?? null;

            // If we can resolve a relative path, attempt to fetch size/mime from storage
            if ($shouldFetchFileInformation && $name) {
                try {
                    if ($storage->exists($name)) {
                        $size = $storage->size($name);
                        $type = $storage->mimeType($name);
                    }
                } catch (UnableToCheckFileExistence) {
                    // ignore and keep provided values
                }
            }

            // Build URL if missing and we have a relative path
            if (! $url && $name) {
                $url = UploadedFile::urlFromPath($name, $component->getDiskName());
            }

            return [
                'name' => (string) ($name ?? $url ?? ''),
                'size' => $size,
                'type' => (string) ($type ?? ''),
                'url' => $url,
            ];
        }

        // $file is a string path
        // Don't return null if file doesn't exist on disk yet —
        // Filament v3.3+ keeps new uploads in Livewire tmp until form submit.
        // Just return the URL; the preview uses Livewire's temp URL for new files.
        try {
            if ($shouldFetchFileInformation && $storage->exists($file)) {
                $size = $storage->size($file);
                $type = $storage->mimeType($file);
            } else {
                $size = 0;
                $type = null;
            }
        } catch (\Throwable) {
            $size = 0;
            $type = null;
        }

        return [
            'name' => (string) ($file ?? ''),
            'size' => $size,
            'type' => (string) ($type ?? ''),
            'url' => UploadedFile::urlFromPath($file, $component->getDiskName()),
        ];
    }
}
