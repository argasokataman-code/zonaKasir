<?php

namespace App\Filament\Tenant\Pages;

use App\Services\Tenants\CategoryReportService;
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

class CategoryReport extends Page implements HasActions, HasForms
{
    use HasTranslatableResource, InteractsWithFormActions, InteractsWithForms;

    protected static ?string $title = '';

    public static ?string $label = 'Sales per Category';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.tenant.pages.category-report';

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
            $this->generate(new CategoryReportService);
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

    public function generate(CategoryReportService $service)
    {
        try {
            $this->validate([
                'data.start_date' => 'required',
                'data.end_date' => 'required',
            ]);
        } catch (\Exception $e) {
            return;
        }

        $this->reports = $service->generate($this->data);
    }

    public function downloadPdf()
    {
        $this->validate([
            'data.start_date' => 'required',
            'data.end_date' => 'required',
        ]);

        return $this->redirectRoute('category-report.generate', $this->data);
    }
}