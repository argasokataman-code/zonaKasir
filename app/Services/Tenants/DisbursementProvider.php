<?php

namespace App\Services\Tenants;

interface DisbursementProvider
{
    /**
     * Send fund to a bank account.
     * @return array{id: string, status: string, ...provider-specific-fields}
     * @throws DisbursementFailedException
     */
    public function send(array $params): array;

    /**
     * Check status of a previous disbursement.
     */
    public function status(string $disburseId): array;
}

class DisbursementFailedException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
