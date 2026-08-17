<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration (FR-9.5)
    |--------------------------------------------------------------------------
    |
    | Onprem daily database backup via pg_dump. Self-managed: backups stored
    | locally. Managed: optionally copy to remote storage.
    |
    */

    'path' => env('BACKUP_PATH', storage_path('app/backups/db')),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 7),

    'compress' => env('BACKUP_COMPRESS', true),
];
