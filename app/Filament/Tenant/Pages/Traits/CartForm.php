<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Features\Member as FeaturesMember;
use App\Features\Voucher;
use App\Models\Tenants\Member;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Setting;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\RawJs;

trait CartForm
{
    protected function getForms(): array
    {
        return [
            'storeCartForm',
        ];
    }

    public function storeCartForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('member_id')
                    ->visible(hasFeatureAndPermission(FeaturesMember::class))
                    ->label('Member')
                    ->getSearchResultsUsing(function (string $search): array {
                        return Member::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->hiddenLabel()
                    ->extraAttributes([
                        'id' => 'memberSelect',
                        'class' => 'hidden',
                    ])
                    ->searchable(),
                RichEditor::make('note')
                    ->hiddenLabel()
                    ->extraAttributes([
                        'id' => 'noteInput',
                        'class' => 'hidden',
                    ]),
                TextInput::make('voucher')
                    ->hiddenLabel()
                    ->extraAttributes([
                        'id' => 'voucherInput',
                        'class' => 'hidden',
                    ])
                    ->visible(hasFeatureAndPermission(Voucher::class)),
                TextInput::make('discount_price')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->prefix(Setting::get('currency', 'IDR'))
                    ->extraAttributes([
                        'id' => 'discountInput',
                        'class' => 'hidden',
                    ])
                    ->hiddenLabel()
                    ->label(__('Manual Discount')),
            ])
            ->statePath('cartDetail')
            ->model(Selling::class);
    }
}