<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PaymentMethodResource\Pages;
use App\Models\Tenants\PaymentMethod;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->translateLabel()
                ->required()
                ->maxLength(255),
            Select::make('payment_type')
                ->translateLabel()
                ->options([
                    'cash' => __('Cash'),
                    'qris' => 'QRIS',
                    'credit' => __('Credit'),
                ])
                ->required()
                ->native(false),
            FileUpload::make('icon')
                ->label(__('QRIS Image'))
                ->disk(config('filesystems.upload_disk'))
                ->directory('payment-methods')
                ->image()
                ->imageEditor()
                ->maxSize(config('upload.livewire_max_size'))
                ->helperText(__('Recommended: 300x300px square, PNG/JPG. Image will be displayed to customers for scanning.'))
                ->visible(fn (Get $get): bool => $get('payment_type') === 'qris')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable(),
                TextColumn::make('payment_type')
                    ->label(__('Payment Type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'credit' => 'danger',
                        'qris' => 'warning',
                        default => 'gray',
                    })
                    ->translateLabel(),
                ImageColumn::make('icon')
                    ->label(__('QRIS Image'))
                    ->disk(config('filesystems.upload_disk'))
                    ->circular()
                    ->size(40)
                    ->visible(fn (?PaymentMethod $record): bool => $record?->payment_type === 'qris'),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->translateLabel()
                    ->icon(fn (PaymentMethod $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (PaymentMethod $record): string => $record->is_active ? 'danger' : 'success')
                    ->label(fn (PaymentMethod $record): string => $record->is_active ? __('Deactivate') : __('Activate'))
                    ->action(function (PaymentMethod $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? __('Payment method activated') : __('Payment method deactivated'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
