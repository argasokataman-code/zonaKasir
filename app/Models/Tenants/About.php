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
use App\Models\Traits\HasTenant;
class About extends Model
{
    use HasTenant;
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
                    \Filament\Forms\Components\Select::make('bank_name')
                        ->label('Bank Name')
                        ->placeholder('Pilih bank')
                        ->options([
                            'BCA' => 'BCA',
                            'BNI' => 'BNI',
                            'BRI' => 'BRI',
                            'Mandiri' => 'Mandiri',
                            'CIMB Niaga' => 'CIMB Niaga',
                            'BSI' => 'BSI (Bank Syariah Indonesia)',
                            'BTN' => 'BTN',
                            'Danamon' => 'Danamon',
                            'Maybank' => 'Maybank',
                            'Permata' => 'Permata',
                            'OCBC NISP' => 'OCBC NISP',
                            'Panin' => 'Panin',
                            'Bank Mega' => 'Bank Mega',
                            'Bank Jatim' => 'Bank Jatim',
                            'Bank Jateng' => 'Bank Jateng',
                            'Bank Jabar Banten' => 'Bank Jabar Banten (BJB)',
                            'Bank Sumut' => 'Bank Sumut',
                            'GoPay' => 'GoPay',
                            'OVO' => 'OVO',
                            'DANA' => 'DANA',
                            'LinkAja' => 'LinkAja',
                        ])
                        ->searchable()
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
                    \Filament\Forms\Components\Select::make('bank_code')
                        ->label('Bank Code')
                        ->placeholder('Pilih kode bank')
                        ->options([
                            '014' => '014 - BCA',
                            '009' => '009 - BNI',
                            '002' => '002 - BRI',
                            '008' => '008 - Mandiri',
                            '022' => '022 - CIMB Niaga',
                            '451' => '451 - BSI',
                            '200' => '200 - BTN',
                            '011' => '011 - Danamon',
                            '016' => '016 - Maybank',
                            '013' => '013 - Permata',
                            '028' => '028 - OCBC NISP',
                            '019' => '019 - Panin',
                            '426' => '426 - Bank Mega',
                            '004' => '004 - Bank Jatim',
                            '006' => '006 - Bank Jateng',
                            '425' => '425 - BJB',
                            '046' => '046 - Bank Sumut',
                        ])
                        ->searchable()
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