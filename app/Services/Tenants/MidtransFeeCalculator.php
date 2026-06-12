<?php

namespace App\Services\Tenants;

class UnknownPaymentTypeException extends \RuntimeException {}

class MidtransFeeCalculator
{
    /**
     * Calculate fees. Uses `double` to match codebase convention (sellings table).
     *
     * @return array{fee_midtrans: float, fee_midtrans_rate_type: string, fee_midtrans_rate_value: float, fee_platform: float, net_amount: float}
     */
    public function calculate(string $paymentType, float $grossAmount, float $platformFeePercent): array
    {
        $feeConfig = config('midtrans.fees.' . $paymentType);

        if ($feeConfig === null) {
            throw new UnknownPaymentTypeException(
                "Unknown payment type: {$paymentType}. Configure in config/midtrans.php"
            );
        }

        $feeMidtrans = match ($feeConfig['type']) {
            'percentage' => round($grossAmount * $feeConfig['percentage'] / 100, 0),
            'flat'       => (float) $feeConfig['amount'],
            default      => throw new UnknownPaymentTypeException("Unknown fee type: {$feeConfig['type']}"),
        };

        $feePlatform = round($grossAmount * $platformFeePercent / 100, 0);
        $netAmount = $grossAmount - $feeMidtrans - $feePlatform;

        return [
            'fee_midtrans' => $feeMidtrans,
            'fee_midtrans_rate_type' => $feeConfig['type'],
            'fee_midtrans_rate_value' => (float) ($feeConfig['percentage'] ?? $feeConfig['amount']),
            'fee_platform' => $feePlatform,
            'net_amount' => $netAmount,
        ];
    }
}
