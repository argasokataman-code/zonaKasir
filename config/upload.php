<?php

$mimeTypes = ['jpeg', 'png', 'jpg', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
$maxSize = 5120; // Kilobytes (5 MB)
$livewireMaxSize = 12288; // Kilobytes (12 MB)

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Disks
    |--------------------------------------------------------------------------
    |
    | upload_disk is used for permanent file storage.
    | tmp_disk is used for temporary file uploads before they are processed.
    |
    */
    'disk' => env('UPLOAD_DISK', 'public'),
    'tmp_disk' => env('TMP_DISK', 'tmp'),

    /*
    |--------------------------------------------------------------------------
    | Upload Validation Rules
    |--------------------------------------------------------------------------
    |
    | These rules are used for API file upload validation. Livewire temporary
    | uploads may use a separate max size because Livewire stores files first.
    |
    */
    'mime_types' => $mimeTypes,
    'max_size' => $maxSize,
    'livewire_max_size' => $livewireMaxSize,

    'rules' => [
        'required',
        'file',
        'mimes:' . implode(',', $mimeTypes),
        "max:{$maxSize}",
    ],

    'livewire_rules' => [
        'required',
        'file',
        'mimes:' . implode(',', $mimeTypes),
        "max:{$livewireMaxSize}",
    ],

    'preview_mimes' => [
        'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
        'mov', 'avi', 'wmv', 'mp3', 'm4a',
        'jpg', 'jpeg', 'mpga', 'webp', 'wma',
    ],

    'max_upload_time' => 5,
    'directory' => env('LIVEWIRE_TMP_DIRECTORY', 'livewire-tmp'),
];