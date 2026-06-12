<?php

namespace App\Http\Resources;

use App\Models\Tenants\Withdrawal;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/**
 * @mixin Withdrawal
 */
class WithdrawalCollection extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'amount'               => $this->amount,
            'bank_name'            => $this->bank_name,
            'bank_account_name'    => $this->bank_account_name,
            'bank_account_number'  => $this->bank_account_number,
            'bank_code'            => $this->bank_code,
            'status'               => $this->status,
            'disburse_id'          => $this->disburse_id,
            'notes'                => $this->notes,
            'rejection_reason'     => $this->rejection_reason,
            'requested_by'         => $this->whenLoaded('requestedBy', fn () => $this->requestedBy->name),
            'approved_by'          => $this->whenLoaded('approvedBy', fn () => $this->approvedBy->name),
            'rejected_by'          => $this->whenLoaded('rejectedBy', fn () => $this->rejectedBy->name),
            'processed_at'         => $this->processed_at,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
