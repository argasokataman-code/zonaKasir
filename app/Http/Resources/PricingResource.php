<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $featureLabels = config('plans.features', []);
        $features = collect($this->features ?? [])->mapWithKeys(fn ($f) => [
            $f => $featureLabels[$f] ?? $f,
        ]);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price_monthly' => (int) $this->price_monthly,
            'price_yearly' => $this->price_yearly ? (int) $this->price_yearly : null,
            'features' => $features,
            'max_stores' => $this->max_stores,
            'max_users' => $this->max_users,
            'is_popular' => $this->is_popular ?? false,
            'cta' => match (true) {
                $this->slug === 'on-premise' => ['label' => __('Konsultasi Sekarang'), 'action' => 'contact'],
                $this->price_monthly <= 0 => ['label' => __('Daftar Gratis'), 'action' => 'register'],
                $this->slug === 'enterprise' => ['label' => __('Hubungi Sales'), 'action' => 'contact'],
                default => ['label' => __('Coba Gratis 30 Hari'), 'action' => 'register'],
            },
            'is_on_premise' => $this->slug === 'on-premise',
        ];
    }
}
