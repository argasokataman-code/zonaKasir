<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebPConverter
{
    /**
     * Convert uploaded file to WebP, store it, delete original.
     * Returns WebP path.
     */
    public static function convert(UploadedFile $file, string $disk, string $directory): string
    {
        $tmpPath = $file->store('tmp', $disk);
        $fullTmp = Storage::disk($disk)->path($tmpPath);

        if (! file_exists($fullTmp)) {
            return $tmpPath;
        }

        $info = @getimagesize($fullTmp);
        if ($info === false) {
            return $tmpPath;
        }

        $mime = $info['mime'];
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
            return $tmpPath;
        }

        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($fullTmp),
            'image/png'  => imagecreatefrompng($fullTmp),
            'image/gif'  => imagecreatefromgif($fullTmp),
        };

        if (! $source) {
            return $tmpPath;
        }

        $filename = Str::before($file->getClientOriginalName(), '.') . '.webp';
        $destPath = $directory . '/' . $filename;
        $fullDest = Storage::disk($disk)->path($destPath);

        @mkdir(dirname($fullDest), 0755, true);
        $ok = imagewebp($source, $fullDest, 85);
        imagedestroy($source);

        Storage::disk($disk)->delete($tmpPath);

        return $ok ? $destPath : $tmpPath;
    }

    /**
     * Filament FileUpload saveFilesAs callback.
     */
    public static function saveAs(string $disk, string $directory): \Closure
    {
        return fn (UploadedFile $file) => self::convert($file, $disk, $directory);
    }
}
