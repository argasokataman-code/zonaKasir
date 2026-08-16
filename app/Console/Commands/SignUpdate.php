<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SignUpdate extends Command
{
    protected $signature = 'onprem:sign-update
                            {--zip= : Path to the update ZIP file, required }
                            {--out= : Output signature path (default: <zip>.sig) }';

    protected $description = 'Sign an update ZIP (run on vendor side, requires ONPREM_PRIVATE_KEY)';

    public function handle(): int
    {
        $zipPath = $this->option('zip');
        $privateKey = config('onprem.license.private_key');

        if (! $zipPath || ! is_file($zipPath)) {
            $this->error('--zip must point to an existing file');

            return Command::FAILURE;
        }

        if (! $privateKey) {
            $this->error('ONPREM_PRIVATE_KEY not set (vendor side secret, never ship to client)');

            return Command::FAILURE;
        }

        $hash = hash_file('sha256', $zipPath);
        if (! openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $this->error('Failed to sign: '.openssl_error_string());

            return Command::FAILURE;
        }

        $out = $this->option('out') ?: $zipPath.'.sig';
        File::put($out, base64_encode($signature));

        $this->info('Signed '.basename($zipPath).' -> '.$out);
        $this->line('SHA256: '.$hash);

        return Command::SUCCESS;
    }
}
