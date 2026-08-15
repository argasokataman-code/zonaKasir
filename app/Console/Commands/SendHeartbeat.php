<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SendHeartbeat extends Command
{
    protected $signature = 'onprem:heartbeat';

    protected $description = 'Send on-prem heartbeat to vendor (silent fail, kasir never affected)';

    public function handle(): int
    {
        $url = config('onprem.heartbeat.url');
        $token = config('onprem.heartbeat.token');
        $instanceId = config('onprem.heartbeat.instance_id') ?: $this->instanceId();

        if (! $url || ! $token || ! $instanceId) {
            $this->line('Heartbeat not configured (ONPREM_HEARTBEAT_URL/TOKEN/INSTANCE_ID). Skipping.');

            return Command::SUCCESS;
        }

        try {
            $response = Http::withHeaders(['X-Onprem-Token' => $token])
                ->timeout(10)
                ->post($url, [
                    'instance_id' => $instanceId,
                    'domain' => config('app.url'),
                    'license_key' => config('onprem.license.key'),
                    'app_version' => app()->version(),
                    'php_version' => PHP_VERSION,
                ]);

            if ($response->successful()) {
                $this->info('Heartbeat sent.');

                return Command::SUCCESS;
            }

            $this->warn('Heartbeat failed: '.$response->status().' '.$response->body());

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            // Silent fail — on-prem must never break on network issues
            $this->warn('Heartbeat error: '.$e->getMessage());

            return Command::SUCCESS;
        }
    }

    private function instanceId(): ?string
    {
        $path = storage_path('app/onprem-instance-id');

        if (is_file($path)) {
            return trim(file_get_contents($path));
        }

        $id = (string) \Illuminate\Support\Str::uuid();
        file_put_contents($path, $id);

        return $id;
    }
}
