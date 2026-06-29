<?php

namespace App\Models\Tenants;

use App\Filament\Tenant\Resources\Traits\HasUploadFileField;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;

/**
 * @mixin IdeHelperProfile
 */
use App\Models\Traits\HasTenant;
class Profile extends Model
{
    use HasTenant;
    use HasFactory,
        HasUploadFileField;
    use LogsActivity;

    protected $fillable = [
        'phone',
        'address',
        'locale',
        'photo',
        'timezone',
        'dark_mode',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['phone', 'address', 'locale', 'timezone', 'photo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(array $key = ['*']): Profile
    {
        return Profile::select($key)->whereUserId(auth()->id())->first() ?? new Profile();
    }

    public static function form(): array
    {
        return [
            TextInput::make('name')
                ->label(__('filament-panels::pages/auth/edit-profile.form.name.label'))
                ->required()
                ->maxLength(255)
                ->autofocus(),
            TextInput::make('email')
                ->label(__('filament-panels::pages/auth/edit-profile.form.email.label'))
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TimezoneSelect::make('timezone')
                ->translateLabel()
                ->searchable(),
            Select::make('locale')
                ->label(__('Language'))
                ->selectablePlaceholder(false)
                ->default('en')
                ->options([
                    'id' => 'Bahasa Indonesia',
                    'en' => 'English',
                    'es' => 'Español',
                ]),
            FileUpload::make('photo')
                ->disk(config('filesystems.upload_disk'))
                ->placeholder(__('Tarik dan lepas file di sini atau klik untuk mencari file'))
                ->directory('profile')
                ->imageResizeMode('cover')
                ->imageCropAspectRatio('1:1')
                ->imageResizeTargetWidth('1024')
                ->imageResizeTargetHeight('1024')
                ->imageResizeUpscale(true)
                ->imageEditor()
                ->image()
                ->maxSize(config('upload.livewire_max_size'))
                ->getUploadedFileUsing(function ($file, string|array|null $storedFileNames, $component) {
                    $static = new static;

                    return $static->getUploadedFileUsing($component, $file, $storedFileNames);
                })
                ->imageEditorMode(2)
                ->translateLabel(),
            Toggle::make('dark_mode')
                ->label(__('Dark Mode'))
                ->helperText(__('Toggle dark/light mode for your account'))
                ->onColor('primary')
                ->offColor('gray')
                ->live()
                ->afterStateUpdated(fn ($state, $livewire) => $livewire->dispatch('dark-mode-toggle', dark: (bool) $state)),
            TextInput::make('password')
                ->label(__('filament-panels::pages/auth/edit-profile.form.password.label'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->rule(Password::default())
                ->autocomplete('new-password')
                ->dehydrated(fn ($state): bool => filled($state))
                ->live(debounce: 500)
                ->same('password_confirmation'),
            TextInput::make('password_confirmation')
                ->label(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.label'))
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->required()
                ->visible(fn (Get $get): bool => filled($get('password'))),
            Actions::make([
                Action::make('Save')
                    ->translateLabel()
                    ->action('saveProfile'),
            ]),
        ];
    }
}