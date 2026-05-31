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
                'name' => $name ?? $url,
                'size' => $size,
                'type' => $type,
                'url' => $url,
            ];
        }

        // $file is a string path (legacy behavior)
        if ($shouldFetchFileInformation) {
            try {
                if (! $storage->exists($file)) {
                    return null;
                }
            } catch (UnableToCheckFileExistence) {
                return null;
            }
        }

        return [
            'name' => $file,
            'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
            'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
            'url' => UploadedFile::urlFromPath($file, $component->getDiskName()),
        ];
    }
}
