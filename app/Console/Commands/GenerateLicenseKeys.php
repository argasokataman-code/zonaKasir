<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateLicenseKeys extends Command
{
    protected $signature = 'onprem:keypair {--out=storage/app/onprem}';

    protected $description = 'Generate RSA keypair for on-prem licenses (run once on vendor side)';

    public function handle(): int
    {
        $out = $this->option('out');
        $dir = base_path($out);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $res = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $privateKey);
        $details = openssl_pkey_get_details($res);

        file_put_contents("{$dir}/private.pem", $privateKey);
        file_put_contents("{$dir}/public.pem", $details['key']);

        chmod("{$dir}/private.pem", 0600);

        $this->info("Keypair written to {$dir}/:");
        $this->line('  private.pem — VENDOR ONLY, never ship to client, never commit');
        $this->line('  public.pem  — put value in client .env ONPREM_PUBLIC_KEY');
        $this->line('');
        $this->line('Public key (for client .env):');
        $this->line('  '.str_replace("\n", '\n', $details['key']));

        return Command::SUCCESS;
    }
}
