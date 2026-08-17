<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebPFileUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->saveUploadedFileUsing(function (FileUpload $component, $file) {
            if (! $file->exists()) {
                return null;
            }

            $disk = $component->getDiskName();
            $directory = $component->getDirectory();
            $filename = $component->getUploadedFileNameForStorage($file);
            $visibility = $component->getVisibility();

            $storeMethod = $visibility === 'public' ? 'storePubliclyAs' : 'storeAs';
            $originalPath = $file->{$storeMethod}($directory, $filename, $disk);

            $fullPath = Storage::disk($disk)->path($originalPath);
            $info = @getimagesize($fullPath);

            if (! $info || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/gif'])) {
                return $originalPath;
            }

            $source = match ($info['mime']) {
                'image/jpeg' => @imagecreatefromjpeg($fullPath),
                'image/png'  => @imagecreatefrompng($fullPath),
                'image/gif'  => @imagecreatefromgif($fullPath),
                default => null,
            };

            if (! $source) {
                return $originalPath;
            }

            $webpFilename = Str::before(pathinfo($filename, PATHINFO_FILENAME), '.') . '.webp';
            $webpPath = $directory . '/' . $webpFilename;
            $fullWebp = Storage::disk($disk)->path($webpPath);
            @mkdir(dirname($fullWebp), 0755, true);

            if (imagewebp($source, $fullWebp, 85)) {
                imagedestroy($source);
                Storage::disk($disk)->delete($originalPath);
                return $webpPath;
            }

            imagedestroy($source);
            return $originalPath;
        });
    }
}
