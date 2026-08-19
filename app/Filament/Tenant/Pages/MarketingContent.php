<?php

namespace App\Filament\Tenant\Pages;

use App\Services\Tenants\MarketingContentService;
use App\Traits\HasTranslatableResource;
use Filament\Pages\Page;

class MarketingContent extends Page
{
    use HasTranslatableResource;

    public static ?string $label = 'Konten Hari Ini';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static string $view = 'filament.tenant.pages.marketing-content';

    public array $bestSellers = [];

    public string $caption = '';

    public array $peakHours = [];

    public array $memberThanks = [];

    public function mount(MarketingContentService $service)
    {
        $this->refresh($service);
    }

    public static function canAccess(): bool
    {
        return can('read selling');
    }

    public function refresh(MarketingContentService $service): void
    {
        $this->bestSellers = $service->bestSellers()->toArray();
        $this->caption = $service->caption();
        $this->peakHours = $service->peakHours()->toArray();
        $this->memberThanks = $service->memberThanks()->toArray();
    }
}
