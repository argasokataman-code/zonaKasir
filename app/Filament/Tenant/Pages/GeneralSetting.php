<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\Traits\RefreshThePage;
use App\Models\Tenants\About;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\UploadedFile;
use App\Models\Tenants\User;
use App\Services\Tenants\AboutService;
use App\Traits\HasTranslatableResource;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Laravel\Pennant\Feature;

class GeneralSetting extends Page implements HasActions, HasForms
{
    use HasTranslatableResource,
        InteractsWithFormActions,
        InteractsWithForms,
        RefreshThePage;

    public static ?string $label = 'General Setting';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.tenant.pages.general-setting';

    public static function canAccess(): bool
    {
        return can('access general setting');
    }

    public $about = [
        'shop_location' => '',
        'photo' => [],
    ];

    public $setting = [];

    public $feature = [];

    public $profile = [];

    public function mount(): void
    {
        $about = About::first()?->toArray() ?? $this->about;
        if ($about) {
            $about['preview_image'] = $about['photo'];
            if ($about['photo']) {
                $about['photo'] = [$about['photo']];
            }
            foreach (config('setting.key') as $key) {
                $this->setting[$key] = Setting::get($key);
            }
            $this->about = $about;
        }

        $this->feature = [
            'supplier' => Feature::active('supplier'),
            'purchasing' => Feature::active('purchasing'),
            'receivable' => Feature::active('receivable'),
            'stock-opname' => Feature::active('stock-opname'),
            'voucher' => Feature::active('voucher'),
            'product-import' => Feature::active('product-import')
        ];

        $user = auth()->user();
        $profile = $user->profile ?? $user->profile()->create();

        // DEBUG: log mount profile loading
        \Log::info('GeneralSetting mount', [
            'user_id' => $user->id,
            'profile_id' => $profile?->id,
            'profile_locale' => $profile?->locale,
            'profile_timezone' => $profile?->timezone,
            'profile_phone' => $profile?->phone,
            'profile_address' => $profile?->address,
            'profile_tenant_id' => $profile?->tenant_id,
            'user_tenant_id' => $user->tenant_id,
        ]);

        // Prepare profile data and normalize photo to the FileUpload expected structure
        $photoState = null;
        if ($profile?->photo) {
            // Prefer UploadedFile record when available (contains metadata)
            $uploaded = UploadedFile::where('relative_path', $profile->photo)
                ->orWhere('url', $profile->photo)
                ->first();

            if ($uploaded) {
                $photoState = [[
                    'name' => $uploaded->relative_path ?: $uploaded->name,
                    'size' => $uploaded->size ?? 0,
                    'type' => $uploaded->mime_type ?? null,
                    'url' => $uploaded->url,
                ]];
            } else {
                // Fallback to disk lookup for legacy relative-path-only values
                $uploadDisk = config('filesystems.upload_disk');
                try {
                    if (Storage::disk($uploadDisk)->exists($profile->photo)) {
                        $photoState = [[
                            'name' => ltrim($profile->photo, '/'),
                            'size' => Storage::disk($uploadDisk)->size($profile->photo),
                            'type' => Storage::disk($uploadDisk)->mimeType($profile->photo),
                            'url' => UploadedFile::urlFromPath($profile->photo, $uploadDisk),
                        ]];
                    }
                } catch (\Exception $e) {
                    $photoState = null;
                }
            }
        }

        $this->profile = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $profile?->phone,
            'address' => $profile?->address,
            'locale' => $profile?->locale ?? 'en',
            'timezone' => $profile?->timezone,
            'photo' => $photoState,
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('About')
                        ->statePath('about')
                        ->translateLabel()
                        ->schema(About::form()),
                    Tabs\Tab::make('App')
                        ->statePath('setting')
                        ->translateLabel()
                        ->schema([
                            Select::make('currency')
                                ->options([
                                    'IDR' => 'IDR',
                                    'MXN' => 'MXN',
                                    'USD' => 'USD',
                                ])
                                ->translateLabel(),
                            Select::make('minimum_stock_nofication')
                                ->options([
                                    0 => 0,
                                    5 => 5,
                                    10 => 10,
                                    20 => 20,
                                    50 => 50,
                                ])
                                ->translateLabel(),
                            Select::make('default_tax')
                                ->options([
                                    0 => '0%',
                                    1 => '1%',
                                    2 => '2%',
                                    3 => '3%',
                                    5 => '5%',
                                    10 => '10%',
                                    11 => '11%',
                                    12 => '12%',
                                ])
                                ->translateLabel(),
                            Actions::make([
                                Action::make('Save')
                                    ->translateLabel()
                                    ->requiresConfirmation()
                                    ->action('saveApp'),
                            ]),
                        ]),
                    Tabs\Tab::make('Feature')
                        ->statePath('feature')
                        ->visible(can('access feature flag'))
                        ->translateLabel()
                        ->schema([
                            Section::make([
                                Checkbox::make('supplier')->inline(),
                                Checkbox::make('purchasing')->inline(),
                                Checkbox::make('receivable')->inline(),
                                Checkbox::make('stock-opname')->inline(),
                                Checkbox::make('voucher')->inline(),
                                Checkbox::make('product-import')->inline(),
                            ]),
                            Actions::make([
                                Action::make('Save')
                                    ->translateLabel()
                                    ->requiresConfirmation()
                                    ->action('saveFeature'),
                            ]),
                        ]),
                    Tabs\Tab::make('Profile')
                        ->statePath('profile')
                        ->translateLabel()
                        ->schema(Profile::form()),
            Tabs\Tab::make('Payment Gateway')
                ->statePath('about')
                ->translateLabel()
                ->schema(About::paymentGatewayForm()),
                ]),
        ]);
    }

    public function saveApp(): void
    {
        foreach ($this->setting as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    /**
     * Store a TemporaryUploadedFile on the tmp disk and create an UploadedFile record.
     * Returns the UploadedFile ID for use with the ID-based lookup flow.
     */
    private function storeAsUploadedFile(TemporaryUploadedFile $image): int
    {
        $tmpDisk = config('filesystems.tmp_disk');
        $filename = $image->getFilename();

        $image->storePubliclyAs('', $filename, $tmpDisk);

        $fullUrl = UploadedFile::urlFromPath($filename, $tmpDisk);

        $uploadedFile = UploadedFile::create([
            'name' => $filename,
            'original_name' => $image->getClientOriginalName(),
            'url' => $fullUrl,
            'mime_type' => $image->getMimeType(),
            'extension' => $image->extension(),
            'size' => $image->getSize(),
            'relative_path' => $filename,
            'path' => '',
            'disk' => $tmpDisk,
        ]);

        return $uploadedFile->id;
    }

    public function saveAbout(AboutService $aboutService): void
    {
        $this->validate([
            'about.shop_name' => 'required',
            'about.business_type' => 'required',
            'about.shop_location' => 'required',
        ]);

        if (filled($this->about['photo'] ?? null)) {
            $photo = array_values($this->about['photo'])[0] ?? null;
            if ($photo instanceof TemporaryUploadedFile) {
                $this->about['uploaded_file_id'] = $this->storeAsUploadedFile($photo);
                $this->about['photo'] = null;
            } elseif (is_string($photo)) {
                $this->about['photo'] = $photo;
            }
        }

        $aboutService->createOrUpdate($this->about);

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    public function saveFeature(): void
    {
        if (can('access feature flag')) {
            foreach ($this->feature as $name => $value) {
                if ($value) {
                    Feature::activate($name);
                } else {
                    Feature::deactivate($name);
                }
            }

            Notification::make()
                ->title(__('Success'))
                ->success()
                ->send();

            $this->mount();
        }
    }

    public function saveProfile(): void
    {
        // DEBUG: log state before validation
        \Log::info('saveProfile called', [
            'profile_state' => $this->profile,
            'user_id' => auth()->id(),
        ]);

        $this->validate([
            'profile.email' => 'required|email',
            'profile.timezone' => 'required',
            'profile.locale' => 'required',
            'profile.password' => 'nullable|confirmed',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $profile = $user->profile;

        if (! $profile) {
            $profile = $user->profile()->create([
                'locale' => 'en',
                'timezone' => 'UTC',
            ]);
        }

        // Pisahkan data User dan Profile
        $userData = [
            'name' => $this->profile['name'] ?? $user->name,
            'email' => $this->profile['email'] ?? $user->email,
        ];
        if (! empty($this->profile['password'])) {
            $userData['password'] = bcrypt($this->profile['password']);
        }

        $profileData = [
            'phone' => $this->profile['phone'] ?? $profile->phone,
            'address' => $this->profile['address'] ?? $profile->address,
            'locale' => $this->profile['locale'] ?? $profile->locale,
            'timezone' => $this->profile['timezone'] ?? $profile->timezone,
        ];

        // DEBUG: log what will be saved
        \Log::info('saveProfile data', [
            'user_data' => $userData,
            'profile_data' => $profileData,
            'profile_id' => $profile->id,
            'profile_exists' => $profile->exists,
        ]);

        // Handle upload photo baru
        try {
            if (isset($this->profile['photo']) && is_array($this->profile['photo']) && count($this->profile['photo']) > 0) {
                $photoVal = array_values($this->profile['photo'])[0];
                if ($photoVal instanceof TemporaryUploadedFile) {
                    $uploadedId = $this->storeAsUploadedFile($photoVal);
                    $tmpFile = UploadedFile::find($uploadedId);
                    if ($tmpFile) {
                        $relativePath = $tmpFile->moveToPublic('profile', $profile->photo ?: null);
                        $profileData['photo'] = $relativePath;
                    }
                } elseif (is_string($photoVal)) {
                    $profileData['photo'] = $photoVal;
                }
            } elseif (isset($this->profile['photo']) && (empty($this->profile['photo']) || $this->profile['photo'] === null)) {
                // Hapus foto jika dihapus di UI
                if ($profile->photo) {
                    $tmpFile = UploadedFile::where('relative_path', $profile->photo)->first()
                        ?? UploadedFile::where('url', $profile->photo)->first();
                    if ($tmpFile) {
                        $tmpFile->deleteFromPublic('profile');
                    } else {
                        $uploadDisk = config('filesystems.upload_disk');
                        if (Storage::disk($uploadDisk)->exists($profile->photo)) {
                            Storage::disk($uploadDisk)->delete($profile->photo);
                        }
                    }
                    $profileData['photo'] = null;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Profile photo upload failed: ' . $e->getMessage());
        }

        $userResult = $user->update($userData);
        $profileResult = $profile->update($profileData);

        // DEBUG: log update results
        \Log::info('saveProfile results', [
            'user_update' => $userResult,
            'profile_update' => $profileResult,
            'profile_was_changed' => $profile->wasChanged(),
            'profile_dirty' => $profile->getDirty(),
        ]);

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }
}
