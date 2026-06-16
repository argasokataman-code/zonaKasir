<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class TurnstileService
{
    public function __construct()
    {
        $this->siteKey = config('turnstile.site_key');
        $this->secretKey = config('turnstile.secret_key');
        $this->enabled = config('turnstile.enabled', false);
    }

    /**
     * Check if Turnstile is enabled and configured.
     * Automatically disabled when domain is not proxied through Cloudflare
     * (no CF-Connecting-IP header) — prevents "Cannot determine Turnstile's
     * embedded location" error on non-Cloudflare domains.
     */
    public function isEnabled(): bool
    {
        if (! $this->enabled || ! filled($this->secretKey)) {
            return false;
        }

        // CF-Connecting-IP only present when domain is proxied (orange cloud)
        if (! Request::header('CF-Connecting-IP')) {
            return false;
        }

        return true;
    }

    /**
     * Get the public site key for the frontend widget.
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * Validate a Turnstile token with Cloudflare's Siteverify API.
     */
    public function validate(?string $token, ?string $remoteip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $data = [
            'secret' => $this->secretKey,
            'response' => $token,
        ];

        if ($remoteip) {
            $data['remoteip'] = $remoteip;
        }

        try {
            $response = Http::timeout(config('turnstile.timeout', 10))
                ->asForm()
                ->post(config('turnstile.siteverify_url'), $data);

            $result = $response->json();

            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * Get the visitor's IP address from request headers.
     */
    public static function getVisitorIp(): string
    {
        return Request::header('CF-Connecting-IP')
            ?? Request::header('X-Forwarded-For')
            ?? Request::ip();
    }
}
