<?php

namespace App\Models\Tenants;

use App\Filament\Tenant\Resources\Traits\HasUploadFileField;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperAbout
 */
class About extends Model
{
    use HasFactory,
        HasUploadFileField;

    protected $guarded = ['id'];

    public static function form(): array
    {
        return [
            TextInput::make('shop_name')
                ->required()
                ->translateLabel(),
            Select::make('business_type')
                ->translateLabel()
                ->options([
                    'retail' => __('Retail'),
                    'wholesale' => __('Wholesale'),
                    'fnb' => __('F&B'),
                    'fashion' => __('Fashion'),
                    'pharmacy' => __('Pharmacy'),
                    'other' => __('Other'),
                ])
                ->live()
                ->required(),
            TextInput::make('other_business_type')
                ->label('Lainnya')
                ->visible(fn (Get $get): bool => $get('business_type') == 'other')
                ->required(fn (Get $get): bool => $get('business_type') == 'other')
                ->string(),
            Textarea::make('shop_location')
                ->required()
                ->translateLabel(),
            FileUpload::make('photo')
                ->disk(config('filesystems.upload_disk'))
                ->placeholder(__('Tarik dan lepas file di sini atau klik untuk mencari file'))
                ->directory('profile')
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('1:1')
                ->imageEditor()
                ->image()                ->maxSize(config('upload.livewire_max_size'))                ->getUploadedFileUsing(function ($file, string|array|null $storedFileNames, $component) {
                    $static = new static;

                    return $static->getUploadedFileUsing($component, $file, $storedFileNames);
                })
                ->imageEditorMode(2)
                ->translateLabel(),
            Actions::make([
                Action::make('Save')
                    ->translateLabel()
                    ->requiresConfirmation()
                    ->action('saveAbout'),
            ]),
        ];
    }

    public static function paymentGatewayForm(): array
    {
        return [
            \Filament\Forms\Components\Section::make('Bank Account')
                ->description(__('Rekening bank untuk pencairan dana dari ZonaKasir'))
                ->schema([
                    \Filament\Forms\Components\TextInput::make('bank_name')
                        ->label('Bank Name')
                        ->placeholder('BCA, Mandiri, BNI, BRI, etc.')
                        ->required()
                        ->translateLabel(),
                    \Filament\Forms\Components\TextInput::make('bank_account_name')
                        ->label('Account Name')
                        ->required()
                        ->translateLabel(),
                    \Filament\Forms\Components\TextInput::make('bank_account_number')
                        ->label('Account Number')
                        ->required()
                        ->translateLabel(),
                    \Filament\Forms\Components\TextInput::make('bank_code')
                        ->label('Bank Code')
                        ->placeholder('014 for BCA, 008 for Mandiri, etc.')
                        ->maxLength(10)
                        ->required()
                        ->translateLabel(),
                ]),
            \Filament\Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('Save')
                    ->translateLabel()
                    ->requiresConfirmation()
                    ->action('saveAbout'),
            ]),
        ];
    }
}