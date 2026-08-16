<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

use function Laravel\Prompts\info;
use function Laravel\Prompts\progress;

class AppUpdateService
{
    private ?string $url;

    private ?array $artisanAfterUpdate;

    private ?array $artisanAfterRestore;

    private ?array $commandsAfterUpdate;

    /** @var callable|null */
    private $logger = null;

    public function __construct(?callable $logger = null)
    {
        $this->url = config('updater.url');

        $this->artisanAfterUpdate = config('updater.artisan_after_update') ?? [];

        $this->artisanAfterRestore = config('updater.artisan_after_restore') ?? [];

        $this->commandsAfterUpdate = config('updater.commands_after_update') ?? [];

        $this->logger = $logger;
    }

    public function update()
    {
        $logger = $this->logger;
        $log = fn ($text) => $logger ? $logger($text) : info($text);

        $log('🔍 Checking for updates...');
        $currentVersion = app(UpdateChecker::class)->getCurrentVersion();

        $response = Http::get($this->url);

        if (! $response->ok()) {
            throw new Exception('Failed to fetch update $log.');
        }

        $latest = $response->json();
        $latestVersion = ltrim($latest['tag_name'], 'v');

        $log('📦 Current version: '.$currentVersion);
        $log('📦 Latest version: '.$latestVersion);

        if (version_compare($latestVersion, $currentVersion, '<=')) {
            $log('✅ You are already on the latest version.');

            return;
        }
        $log('🚀 Updating from v'.$currentVersion.' to v'.$latestVersion.'...');

        $zipUrl = $latest['assets'][0]['browser_download_url'];
        $zipSize = $latest['assets'][0]['size'];
        $zipPath = storage_path('app/update.zip');
        $options = [
            'http' => [
                'header' => "User-Agent: zonaKasirAutoUpdater\r\n",
                'timeout' => 600,
            ],
        ];

        $context = stream_context_create($options);

        $readStream = fopen($zipUrl, 'r', false, $context);
        if (! $readStream) {
            throw new Exception('Failed to open update ZIP stream.');
        }

        $writeStream = fopen($zipPath, 'w');
        if (! $writeStream) {
            throw new Exception('Failed to create ZIPxfile.');
        }

        $chunkSize = 1024 * 512; // 512 KB

        $progress = progress(
            label: '📥 Downloading update...',
            steps: $zipSize,
        );
        $progress->start();

        while (! feof($readStream)) {
            $buffer = fread($readStream, $chunkSize); // 512 KB chunks
            fwrite($writeStream, $buffer);
            $progress->advance(strlen($buffer));
        }
        fclose($readStream);
        fclose($writeStream);

        $progress->finish();

        $log('✅ Download completed.');

        $this->verifyUpdateSignature($zipPath, $latest, $log);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            $log('☕ Extracting downloaded files...');
            $extractPath = storage_path('app/update/zonakasir/');
            $this->extractZipSafely($zip, $extractPath);
            $zip->close();
        } else {
            throw new Exception('❌ Failed to extract zip.');
        }

        $folders = glob(storage_path('app/update/*'), GLOB_ONLYDIR);
        $updateFolder = $folders[0] ?? null;

        if (! $updateFolder) {
            throw new Exception('❌ Update folder not found.');
        }

        $exclude = ['.env', 'storage'];

        $this->copyFolder($updateFolder, base_path(), $exclude, $log);

        unlink($zipPath);
        $log('🗑️ Cleaning up...');
        File::deleteDirectory(storage_path('app/update'));

        foreach ($this->artisanAfterUpdate as $key => $command) {
            $this->runArtisanCommands($key, $command);
        }

        foreach ($this->commandsAfterUpdate as $command) {
            $log('💻 Running command: '.$command);
            exec($command);
        }

        file_put_contents(base_path('version.txt'), $latestVersion);

        $log("✅ Update to v$latestVersion completed.");
        Cache::forget('update:progress');
    }

    /**
     * Verify the downloaded ZIP against a vendor-signed .sig asset.
     * On-prem must refuse unsigned/tampered updates.
     */
    protected function verifyUpdateSignature(string $zipPath, array $latest, callable $log): void
    {
        $publicKey = $this->onpremPublicKey();

        if (! $publicKey) {
            throw new Exception('❌ Update blocked: ONPREM_PUBLIC_KEY not configured.');
        }

        $sigAsset = collect($latest['assets'] ?? [])
            ->first(fn (array $a) => str_ends_with($a['name'], '.sig'));

        if (! $sigAsset) {
            throw new Exception('❌ Update blocked: no .sig asset on release. Vendor must run `php artisan onprem:sign-update`.');
        }

        $log('🔏 Verifying update signature...');
        $sig = file_get_contents($sigAsset['browser_download_url']);
        $signature = base64_decode($sig, true);

        if ($signature === false) {
            throw new Exception('❌ Update blocked: corrupt signature file.');
        }

        $hash = hash_file('sha256', $zipPath);
        $ok = openssl_verify($hash, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($ok !== 1) {
            throw new Exception('❌ Update blocked: signature verification failed. Update ZIP is not from the vendor.');
        }

        $log('✅ Signature valid (SHA256: '.$hash.').');
    }

    protected function onpremPublicKey(): ?string
    {
        $key = config('onprem.license.public_key');

        return $key ?: null;
    }

    /**
     * Extract ZIP entries with zip-slip protection (reject path traversal).
     */
    protected function extractZipSafely(ZipArchive $zip, string $extractPath): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            $name = str_replace('\\', '/', (string) ($entry['name'] ?? ''));

            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                throw new Exception('❌ Update blocked: malicious path in update ZIP ('.$name.').');
            }
        }

        // Only clean the temp update dir. restoreApp() extracts to base_path()
        // and must never delete it.
        if (str_starts_with($extractPath, storage_path('app/update'))) {
            File::deleteDirectory($extractPath);
            File::ensureDirectoryExists($extractPath);
        }

        $zip->extractTo($extractPath);
    }

    protected function copyFolder($from, $to, $exclude, $log)
    {
        $log('📦 Copying files...');
        foreach (File::allFiles($from) as $file) {
            $relativePath = str_replace($from.'/', '', $file->getPathname());
            foreach ($exclude as $skip) {
                if (str_starts_with($relativePath, $skip)) {
                    continue 2;
                }
            }
            $destPath = $to.'/'.$relativePath;
            File::ensureDirectoryExists(dirname($destPath));
            File::copy($file->getPathname(), $destPath);
        }
    }

    public function backupApp()
    {
        $logger = $this->logger;
        $log = fn ($text) => $logger ? $logger($text) : info($text);
        $log('📦 Backing up app...');
        $currentVersion = app(UpdateChecker::class)->getCurrentVersion();
        $path = storage_path('app/backups/app-backup-'.$currentVersion.'.zip');
        $backupDir = dirname($path);
        if (! file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        if (! file_exists($path)) {
            touch($path);
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Could not create backup zip file.');
        }

        $base = base_path();
        $exclude = ['vendor', 'node_modules', 'storage', '.git'];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = str_replace($base.DIRECTORY_SEPARATOR, '', $filePath);

            if (collect($exclude)->contains(fn ($dir) => str_starts_with($relativePath, $dir))) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
        $log('✅ App backed up successfully');
    }

    public function restoreApp()
    {
        $backupDir = storage_path('app/backups');
        $files = File::files($backupDir);

        $latestBackup = collect($files)
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->first();

        if ($latestBackup) {
            $path = $latestBackup->getRealPath();
        } else {
            throw new Exception('No backup files found.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new Exception('Could not open backup zip file.');
        }

        $this->extractZipSafely($zip, base_path());
        $zip->close();

        foreach ($this->artisanAfterRestore as $key => $command) {
            $this->runArtisanCommands($key, $command);
        }

        File::delete($path);
    }

    private function runArtisanCommands(mixed $key, mixed $command)
    {
        $logger = $this->logger;
        $log = fn ($text) => $logger ? $logger($text) : info($text);
        if (is_int($key)) {
            $log('💻 Running artisan command: '.$command);
            Artisan::call($command);
        }
        if (is_string($key)) {
            $log('💻 Running artisan command: '.$key);
            Artisan::call($key, $command);
        }
    }
}
