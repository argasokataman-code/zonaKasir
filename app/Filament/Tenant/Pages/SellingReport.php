<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Pages\Traits\HasReportPageSidebar;
use App\Services\Tenants\SellingReportService;
use App\Traits\HasTranslatableResource;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class SellingReport extends Page implements HasActions, HasForms
{
    use HasReportPageSidebar, HasTranslatableResource, InteractsWithFormActions, InteractsWithForms;

    public static ?string $title = 'Selling Report';

    public static ?string $label = 'Selling Report';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.tenant.pages.selling-report';

    public static function canAccess(): bool
    {
        return can('generate selling report');
    }

    #[Url]
    public ?array $data = [
        'start_date' => null,
        'end_date' => null,
    ];

    public $reports = null;

    public function mount()
    {
        if ($this->data['start_date'] && $this->data['end_date']) {
            $this->generate(new SellingReportService);
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('start_date')
                ->translateLabel()
                ->date()
                ->required()
                ->closeOnDateSelection()
                ->default(now())
                ->native(false),
            DatePicker::make('end_date')
                ->translateLabel()
                ->date()
                ->required()
                ->closeOnDateSelection()
                ->default(now())
                ->native(false),
        ])
            ->columns(2)
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make(__('Generate'))
                ->action('generate'),
            Action::make(__('Print'))
                ->color('warning')
                ->extraAttributes([
                    'id' => 'print-btn',
                ])
                ->icon('heroicon-o-printer'),
            Action::make('download-pdf')
                ->label(__('Download as PDF'))
                ->action('downloadPdf')
                ->color('warning')
                ->icon('heroicon-o-arrow-down-on-square'),
        ];
    }

    public function generate(SellingReportService $sellingReportService)
    {
        try {
            $this->validate([
                'data.start_date' => 'required',
                'data.end_date' => 'required',
            ]);
        } catch (\Exception $e) {
            return;
        }

        $this->reports = $sellingReportService->generate($this->data);
    }

    public function downloadPdf()
    {
        $this->validate([
            'data.start_date' => 'required',
            'data.end_date' => 'required',
        ]);

        return $this->redirectRoute('selling-report.generate', $this->data);
    }
}
