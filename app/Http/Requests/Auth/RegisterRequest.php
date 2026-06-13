<?php

namespace App\Http\Requests\Auth;

use App\Enums\ShopType;
use App\Services\RegisterTenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    public function __construct(public RegisterTenant $registerTenant)
    {
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenant_users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_type' => ['required', Rule::in(ShopType::cases())],
            'other_business_type' => ['required_if:business_type,other'],
        ];
    }

    public function register(): string
    {
        try {
            return $this->registerTenant->create($this->all());
        } catch (ValidationException $e) {
            throw $e;
        }
    }
}
