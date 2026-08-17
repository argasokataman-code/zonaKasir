<?php

namespace App\Filament\Tenant\Pages;

use App\Services\Tenants\KdsService;
use App\Traits\HasTranslatableResource;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class KitchenDisplay extends Page
{
    use HasTranslatableResource;

    protected static ?string $title = '';

    public static ?string $label = 'Kitchen Display';

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static string $view = 'filament.tenant.pages.kitchen-display';

    public array $orders = [];

    public function mount(KdsService $kds)
    {
        $this->refresh($kds);
    }

    public static function canAccess(): bool
    {
        return can('update selling');
    }

    #[On('refresh-kitchen')]
    public function refresh(KdsService $kds): void
    {
        $this->orders = $kds->orders()->toArray();
    }

    public function startCooking(KdsService $kds, int $detailId): void
    {
        $kds->updateStatusById($detailId, 'in_progress');
        $this->refresh($kds);
    }

    public function markDone(KdsService $kds, int $detailId): void
    {
        $kds->updateStatusById($detailId, 'done');
        $this->refresh($kds);
    }
}
