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
use Illuminate\Support\Facades\Http;
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
        $about = About::select('id', 'shop_name', 'shop_location', 'business_type', 'other_business_type', 'bank_name', 'bank_account_name', 'bank_account_number', 'bank_code', 'photo')->first()?->toArray() ?? $this->about;
        if ($about) {
            $about['preview_image'] = $about['photo'];
            // Guard: skip corrupted photo values (empty, array, '[]', etc.)
            $_aboutPhoto = $about['photo'] ?? null;
            if ($_aboutPhoto && is_string($_aboutPhoto) && $_aboutPhoto !== '[]' && $_aboutPhoto !== '') {
                $about['photo'] = [$_aboutPhoto];
            } else {
                $about['photo'] = null;
            }
            foreach (config('setting.key') as $key) {
                $this->setting[$key] = Setting::get($key);
            }
            $this->about = $about;
        }

        // Hanya tampilkan fitur yang ada di plan subscription
        $planAccess = app(\App\Services\PlanAccessService::class);
        $tenantId = auth()->user()?->tenant_id;
        $allowedFeatures = $tenantId ? $planAccess->getCurrentPlanFeatures($tenantId) : [];
        // Semua feature key harus snake_case (sama dengan config/plans.php dan Plan model)
        $allFeatures = ['supplier', 'purchasing', 'receivable', 'stock_opname', 'voucher', 'product_import'];
        $this->feature = [];
        foreach ($allFeatures as $f) {
            if (in_array($f, $allowedFeatures, true)) {
                $this->feature[$f] = Feature::active($f);
            } elseif (Feature::active($f)) {
                // Cleanup: deactivate fitur yang tidak ada di plan tapi sebelumnya diaktifkan
                Feature::deactivate($f);
            }
        }

        $user = auth()->user();
        $profile = $user->profile ?? $user->profile()->create();

        // Prepare profile photo state — SIMPLE STRING path, NOT preview array
        // Guard: skip corrupted photo values (empty, array, '[]', etc.)
        $photoState = null;
        $_photo = $profile?->photo;
        if ($_photo && is_string($_photo) && $_photo !== '[]' && $_photo !== '') {
            $photoState = [$_photo];
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
                    Tabs\Tab::make('Theme')
                        ->statePath('about')
                        ->translateLabel()
                        ->schema(About::themeForm()),
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
                        ->visible(can('access feature flag') && count($this->feature ?? []) > 0)
                        ->translateLabel()
                        ->schema(function () {
                            $allowed = array_keys($this->feature ?? []);
                            $featureLabels = [
                                'supplier' => __('Supplier'),
                                'purchasing' => __('Purchasing'),
                                'receivable' => __('Receivable'),
                                'stock_opname' => __('Stock Opname'),
                                'voucher' => __('Voucher'),
                                'product_import' => __('Product Import'),
                            ];
                            $checkboxes = [];
                            foreach ($allowed as $f) {
                                if (isset($featureLabels[$f])) {
                                    $checkboxes[] = Checkbox::make($f)->label($featureLabels[$f])->inline();
                                }
                            }
                            return [
                                Section::make($checkboxes),
                                Actions::make([
                                    Action::make('Save')
                                        ->translateLabel()
                                        ->requiresConfirmation()
                                        ->action('saveFeature'),
                                ]),
                            ];
                        }),
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
                    // Buat/update UploadedFile record (sama seperti profile photo)
                    $uploadDisk = config('filesystems.upload_disk');
                    if (! UploadedFile::where('relative_path', $photo)->exists()) {
                        UploadedFile::create([
                            'name' => basename($photo),
                            'relative_path' => $photo,
                            'url' => UploadedFile::urlFromPath($photo, $uploadDisk),
                            'disk' => $uploadDisk,
                            'path' => '',
                        ]);
                    }
                }
        }

        $aboutService->createOrUpdate($this->about);

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    public function saveTheme(AboutService $aboutService): void
    {
        $data = array_filter([
            'primary_color' => $this->about['primary_color'] ?? null,
            'logo' => $this->about['logo'] ?? null,
            'dark_mode' => $this->about['dark_mode'] ?? null,
        ], fn ($v) => $v !== null);

        if (filled($data)) {
            $aboutService->createOrUpdate($data);
        }

        Notification::make()
            ->title(__('Theme saved'))
            ->success()
            ->send();

        $this->mount();
    }

    public function saveFeature(): void
    {
        if (! can('access feature flag')) {
            return;
        }

        $planAccess = app(\App\Services\PlanAccessService::class);
        $tenantId = auth()->user()?->tenant_id;
        $planFeatures = $tenantId ? $planAccess->getCurrentPlanFeatures($tenantId) : [];

        foreach ($this->feature as $name => $value) {
            if ($value) {
                // Hanya aktifkan jika fitur ada di plan subscription
                if (in_array($name, $planFeatures, true)) {
                    Feature::activate($name);
                }
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

    public function saveProfile(): void
    {
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

        // Handle upload photo baru
        try {
            if (isset($this->profile['photo']) && is_array($this->profile['photo']) && count($this->profile['photo']) > 0) {
                $photoVal = array_values($this->profile['photo'])[0];
                if ($photoVal instanceof TemporaryUploadedFile) {
                    $uploadedId = $this->storeAsUploadedFile($photoVal);
                    $tmpFile = UploadedFile::select('id', 'name', 'relative_path', 'url', 'disk', 'path')->find($uploadedId);
                    if ($tmpFile) {
                        $relativePath = $tmpFile->moveToPublic('profile', $profile->photo ?: null);
                        $profileData['photo'] = $relativePath;
                    }
                } elseif (is_string($photoVal)) {
                    $profileData['photo'] = $photoVal;
                    // Buat/update UploadedFile record agar mount() temukan di refresh
                    $uploadDisk = config('filesystems.upload_disk');
                    $existing = UploadedFile::select('id', 'relative_path')->where('relative_path', $photoVal)->first();
                    if (! $existing) {
                        // Hapus UploadedFile lama jika ada (ganti foto)
                        if ($profile->photo && $profile->photo !== $photoVal) {
                            UploadedFile::where('relative_path', $profile->photo)
                                ->orWhere('url', $profile->photo)
                                ->delete();
                        }
                        UploadedFile::create([
                            'name' => basename($photoVal),
                            'relative_path' => $photoVal,
                            'url' => UploadedFile::urlFromPath($photoVal, $uploadDisk),
                            'disk' => $uploadDisk,
                            'path' => '',
                        ]);
                    }
                }
            } elseif (isset($this->profile['photo']) && (empty($this->profile['photo']) || $this->profile['photo'] === null)) {
                // Hapus foto jika dihapus di UI
                if ($profile->photo) {
                    $tmpFile = UploadedFile::select('id', 'name', 'relative_path', 'url', 'disk', 'path')->where('relative_path', $profile->photo)->first()
                        ?? UploadedFile::select('id', 'name', 'relative_path', 'url', 'disk', 'path')->where('url', $profile->photo)->first();
                    if ($tmpFile) {
                        $tmpFile->deleteFromPublic('profile');
                    } else {
                        $projectRef = config('services.supabase.project_ref') ?: env('SUPABASE_PROJECT_REF');
                        $serviceRole = config('services.supabase.service_role') ?: env('SUPABASE_SERVICE_ROLE');
                        if ($projectRef && $serviceRole) {
                            $bucket = env('SUPABASE_BUCKET', 'zonakasir');
                            Http::withToken($serviceRole)
                                ->delete("https://{$projectRef}.supabase.co/storage/v1/object/{$bucket}", [
                                    'prefixes' => [$profile->photo],
                                ]);
                        } else {
                            $uploadDisk = config('filesystems.upload_disk');
                            if (Storage::disk($uploadDisk)->exists($profile->photo)) {
                                Storage::disk($uploadDisk)->delete($profile->photo);
                            }
                        }
                    }
                    $profileData['photo'] = null;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Profile photo upload failed: ' . $e->getMessage());
        }

        $user->update($userData);
        $profile->update($profileData);

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }
}
