<?php

namespace App\Rules;

use App\Models\Tenants\Barcode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueBarcode implements ValidationRule
{
    protected ?int $excludeId;

    public function __construct(?int $excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $exists = Barcode::where('code', $value);

        if ($this->excludeId) {
            $exists->where('id', '!=', $this->excludeId);
        }

        if ($exists->exists()) {
            $fail(__('Barcode already exists.'));
        }
    }
}
