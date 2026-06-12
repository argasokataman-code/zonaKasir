<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\PaymentMethodResource\Pages;
use App\Models\Tenants\PaymentMethod;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('payment_type')
                    ->label(__('Payment Type'))
                    ->options(self::paymentTypeOptions())
                    ->native(false)
                    ->live()
                    ->reactive()
                    ->required()
                    ->translateLabel()
                    ->helperText(__('Pilih tipe pembayaran. Nama metode akan otomatis terisi.')),
                TextInput::make('name')
                    ->label(__('Display Name'))
                    ->translateLabel()
                    ->required()
                    ->helperText(__('Nama yang muncul di POS dan struk.')),
            ])
            ->columns(1);
    }

    public static function paymentTypeOptions(): array
    {
        return [
            'cash' => __('Tunai / Cash'),
            'gopay' => 'GoPay (Midtrans)',
            'shopeepay' => 'ShopeePay (Midtrans)',
            'qris' => 'QRIS (Midtrans)',
            'credit_card' => 'Kartu Kredit (Midtrans)',
            'debit_card' => 'Kartu Debit (Midtrans)',
            'bank_transfer' => 'Transfer Bank (Midtrans)',
            'indomaret' => 'Indomaret (Midtrans)',
            'alfamart' => 'Alfamart (Midtrans)',
            'kredivo' => 'Kredivo (Midtrans)',
            'akulaku' => 'Akulaku (Midtrans)',
        ];
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
                        'gopay', 'shopeepay', 'qris' => 'warning',
                        'credit_card', 'debit_card' => 'info',
                        'bank_transfer', 'indomaret', 'alfamart' => 'gray',
                        'kredivo', 'akulaku' => 'danger',
                        default => 'gray',
                    })
                    ->translateLabel(),
                TextColumn::make('is_cash')
                    ->label(__('Type'))
                    ->badge()
                    ->getStateUsing(fn (PaymentMethod $m) => $m->isMidtrans() ? 'Midtrans' : 'Cash')
                    ->color(fn (string $s): string => $s === 'Cash' ? 'success' : 'warning'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
