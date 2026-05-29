<?php

namespace App\Exceptions;

use Exception;

class VoucherException extends Exception
{
    /**
     * Voucher has not been assigned or is invalid
     */
    public static function notAssigned(): self
    {
        return new self('Voucher has not been assigned. Call applyable() first to validate and assign a voucher.');
    }

    /**
     * Voucher code is invalid or expired
     */
    public static function invalid(string $code): self
    {
        return new self("Voucher code '{$code}' is invalid, expired, or has insufficient quota.");
    }

    /**
     * Voucher quota exceeded
     */
    public static function quotaExceeded(string $code): self
    {
        return new self("Voucher code '{$code}' has reached its usage limit.");
    }
}
