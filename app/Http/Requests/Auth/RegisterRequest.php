<?php

namespace App\Http\Requests\Auth;

use App\Enums\ShopType;
use App\Services\RegisterTenant;
use App\Services\TurnstileService;
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_type' => ['required', Rule::in(ShopType::cases())],
            'other_business_type' => ['required_if:business_type,other'],
        ];

        if (app(TurnstileService::class)->isEnabled()) {
            $rules['cf-turnstile-response'] = ['required', 'string'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $turnstile = app(TurnstileService::class);
            if ($turnstile->isEnabled()) {
                $token = $this->input('cf-turnstile-response');
                if (! $turnstile->validate($token, TurnstileService::getVisitorIp())) {
                    $validator->errors()->add(
                        'cf-turnstile-response',
                        'Verification failed. Please try again.'
                    );
                }
            }
        });
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
