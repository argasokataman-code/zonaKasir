<?php

namespace App\Providers\Filament;

use App\Features\Member;
use App\Features\PaymentMethod;
use App\Features\Permission;
use App\Features\Purchasing;
use App\Features\Receivable;
use App\Features\Role;
use App\Features\StockOpname;
use App\Features\Supplier;
use App\Features\User;
use App\Features\Voucher;
use App\Filament\Tenant\Pages\CartItem;
use App\Filament\Tenant\Pages\Cashier;
use App\Filament\Tenant\Pages\CashierReport;
use App\Filament\Tenant\Pages\KitchenDisplay;
use App\Filament\Tenant\Pages\GeneralSetting;
use App\Filament\Tenant\Pages\MarketingContent;
use App\Filament\Tenant\Pages\Printer;
use App\Filament\Tenant\Pages\ProductReport;
use App\Filament\Tenant\Pages\PurchasingReport;
use App\Filament\Tenant\Pages\Report;
use App\Filament\Tenant\Pages\SellingReport;
use App\Filament\Tenant\Pages\ManageSubscription;
use App\Filament\Tenant\Pages\WithdrawalPage;
use App\Filament\Tenant\Pages\TenantLogin;
use App\Filament\Tenant\Resources\CategoryResource;
use App\Filament\Tenant\Resources\BrandResource;
use App\Filament\Tenant\Resources\MemberResource;
use App\Filament\Tenant\Resources\PaymentMethodResource;
use App\Filament\Tenant\Resources\PermissionResource;
use App\Filament\Tenant\Resources\ProductResource;
use App\Filament\Tenant\Resources\PurchasingResource;
use App\Filament\Tenant\Resources\ReceivableResource;
use App\Filament\Tenant\Resources\RoleResource;
use App\Filament\Tenant\Resources\SellingResource;
use App\Filament\Tenant\Resources\SettlementResource;
use App\Filament\Tenant\Resources\StockOpnameResource;
use App\Filament\Tenant\Resources\SupplierResource;
use App\Filament\Tenant\Resources\TableResource;
use App\Filament\Tenant\Resources\UserResource;
use App\Filament\Tenant\Resources\VoucherResource;
use App\Filament\Tenant\Resources\WithdrawalResource;
use App\Http\Middleware\LocalizationMiddleware;
use App\Http\Middleware\TenantIsolationMiddleware;
use App\Models\Tenants\About;
use App\Models\Tenants\UploadedFile;
use App\Tenant;
use Filament\Forms\Components\DatePicker;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Resources\Resource;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;

class TenantPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
        DatePicker::configureUsing(function (DatePicker $datePicker): void {
            $datePicker
                ->closeOnDateSelection()
                ->native(false);
        });

    }

    public function panel(Panel $panel): Panel
    {
        $panel = $this->configurePanel($panel);

        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                    if ($this->aboutsTableExists()) {
                        $about = \App\Models\Tenants\About::cache();
                    if ($about) {
                        $panel->brandName($about->shop_name ?? 'Your Brand');

                        if ($about->logo) {
                            $panel->brandLogo(asset('storage/' . $about->logo));
                        } else {
                            $panel->brandLogo(asset('assets/logo/zonaqasir-text-icon.png'));
                        }

                        if ($about->primary_color) {
                            $panel->colors(['primary' => Color::hex($about->primary_color)]);
                        }

                        // Dark mode: Filament enabled (Alpine + CSS), no user-menu toggle.
                        // Profile.dark_mode synced → localStorage via HEAD script below.
                        $panel->darkMode(true, false);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('TenantPanelProvider panel config error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Sync profile.dark_mode (DB) → localStorage (Alpine) before Filament JS runs.
        // Alpine reads localStorage on init and applies `.dark` class automatically.
        // Also listens for real-time dark-mode-toggle events from Profile form.
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn () => \Illuminate\Support\Facades\Blade::render('<script>
                let dm = @json(auth()->user()?->profile?->dark_mode);
                var shouldBeDark = dm === true || dm === "1" || dm === 1;
                document.documentElement.classList.toggle("dark", shouldBeDark);
                localStorage.setItem("darkMode", shouldBeDark ? "true" : "false");
                window.addEventListener("dark-mode-toggle", function(e) {
                    var isDark = e.detail?.dark === true;
                    document.documentElement.classList.toggle("dark", isDark);
                    localStorage.setItem("darkMode", isDark ? "true" : "false");
                });
            </script>') . view('meta')
        );

        // Global helpers — inline blocking script BEFORE @filamentScripts (Livewire/Alpine)
        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_BEFORE,
            fn () => '<script>window.moneyFormat=function(n,c){var u=c||window.zonakasirCurrency||"IDR",l=window.zonakasirLocale||"en",o={style:"currency",currency:u};u==="IDR"&&(o.minimumFractionDigits=0);return new Intl.NumberFormat(l,o).format(n)};</script>'
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_AFTER,
            fn () => view('partials.trial-banner-sidebar')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            fn () => view('version-indicator')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn () => view('partials.body-start')
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn () => view('partials.body-end')
        );

        return $panel;
    }

    private function configurePanel(Panel $panel): Panel
    {
        $panel
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarFullyCollapsibleOnDesktop()
            ->databaseNotifications()
            ->lazyLoadedDatabaseNotifications()
            ->id('tenant')
            ->viteTheme('resources/css/filament/tenant/theme.css')
            ->colors(['primary' => Color::hex('#FF6600')])
            ->assets([
                // html5-qrcode tetap dari CDN (cannot bundle)
                Js::make('html5-qrcode', 'https://unpkg.com/html5-qrcode'),
                Js::make('session-timeout', resource_path('js/session-timeout.js')),
            ])
            ->favicon('/favicon.ico')
            ->spa(config('app.spa_mode'))
            ->authGuard('web')
            ->path('/member')
            ->brandLogoHeight('4rem')
            ->login(TenantLogin::class)
            ->navigation(fn (NavigationBuilder $navigationBuilder) => $this->buildNavigation($navigationBuilder))
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->middleware($this->getMiddleware())
            ->authMiddleware([
                Authenticate::class,
            ])
            ->pages([
                CartItem::class,
            ]);

        return $panel;
    }

    private function buildNavigation(NavigationBuilder $navigationBuilder): NavigationBuilder
    {
        $nicheHidden = $this->getNicheHiddenKeys();

        $items = array_values(array_filter(
            $this->getNavigationItems(),
            fn ($item) => $item !== null
        ));

        $groups = array_map(function ($group) {
            if (! $group instanceof NavigationGroup) {
                return $group;
            }
            $filteredItems = array_values(array_filter(
                $group->getItems(),
                fn ($item) => $item !== null
            ));

            return $group->items($filteredItems);
        }, $this->getNavigationGroups());

        return $navigationBuilder
            ->items($items)
            ->groups($groups);
    }

    private function getNicheHiddenKeys(): array
    {
        $bt = $this->getBusinessType();
        if (! $bt) {
            return [];
        }

        return config("niches.nav.{$bt}.hidden", []);
    }

    private function getNavigationItems(): array
    {
        $items = [
            ...Pages\Dashboard::getNavigationItems(),
            $this->generateNavigationItem(Cashier::class),
            $this->generateNavigationItem(KitchenDisplay::class, nicheKey: 'kitchen_display'),
            $this->generateNavigationItem(MarketingContent::class, nicheKey: 'marketing_content'),
            $this->generateNavigationItem(SellingResource::class),
            $this->generateNavigationItem(SupplierResource::class, Supplier::class),
            $this->generateNavigationItem(MemberResource::class, Member::class),
            $this->generateNavigationItem(PaymentMethodResource::class, PaymentMethod::class),
            $this->generateNavigationItem(ReceivableResource::class, Receivable::class),
        ];

        return array_filter($items, fn ($item) => $item !== null);
    }

    private function getNavigationGroups(): array
    {
        return [
            NavigationGroup::make(__('Inventory'))->items([
                $this->generateNavigationItem(PurchasingResource::class, Purchasing::class),
                $this->generateNavigationItem(StockOpnameResource::class, StockOpname::class),
                $this->generateNavigationItem(ProductResource::class),
                $this->generateNavigationItem(CategoryResource::class),
                $this->generateNavigationItem(BrandResource::class),
                $this->generateNavigationItem(TableResource::class, nicheKey: 'table'),
            ]),
            NavigationGroup::make(__('User'))->items([
                $this->generateNavigationItem(UserResource::class, User::class),
                $this->generateNavigationItem(RoleResource::class, Role::class),
                $this->generateNavigationItem(PermissionResource::class, Permission::class),
            ]),
            NavigationGroup::make(__('Report'))->label('')->collapsible(false)->items([
                $this->generateNavigationItem(
                    resource: Report::class,
                    activeWhen: [
                        SellingReport::class,
                        ProductReport::class,
                        CashierReport::class,
                        PurchasingReport::class,
                    ]
                ),
            ]),
            NavigationGroup::make(__('General'))->label('')->collapsible(false)->items([
                $this->generateNavigationItem(VoucherResource::class, Voucher::class),
                $this->generateNavigationItem(SettlementResource::class, hideOnPremise: true),
                $this->generateNavigationItem(WithdrawalResource::class, hideOnPremise: true),
                $this->generateNavigationItem(WithdrawalPage::class, hideOnPremise: true),
                $this->generateNavigationItem(ManageSubscription::class),
            ]),
            NavigationGroup::make(__('Setting'))->collapsible(false)->items([
                $this->generateNavigationItem(GeneralSetting::class),
                $this->generateNavigationItem(Printer::class),
            ]),
        ];
    }

    private function getMiddleware(): array
    {
        return [
            \App\Http\Middleware\NoCacheResponse::class,
            \App\Http\Middleware\DynamicPwaManifest::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            TenantIsolationMiddleware::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
            LocalizationMiddleware::class,
            \App\Http\Middleware\CheckSubscription::class,
        ];
    }

    private ?string $businessType = null;

    private function isNonFnbBusiness(): bool
    {
        return ! $this->isBusinessType('fnb');
    }

    private function isBusinessType(string ...$types): bool
    {
        $bt = $this->getBusinessType();

        return $bt && in_array($bt, $types, true);
    }

    private function getBusinessType(): ?string
    {
        if ($this->businessType !== null) {
            return $this->businessType;
        }

        try {
            // Try tenant-aware first (works during request)
            if (function_exists('tenancy') && tenancy()->initialized) {
                if ($this->aboutsTableExists()) {
                    $about = About::cache();
                    $raw = $about?->business_type;
                }
            }

            // Fallback: read from session (set by middleware)
            if (! isset($raw)) {
                $raw = session('tenant_business_type');
            }

            if (! isset($raw)) {
                return null;
            }

            // Map legacy values to config keys
            $aliases = [
                'cafe' => 'fnb',
                'restaurant' => 'fnb',
                'warung' => 'fnb',
                'toko' => 'retail',
                'klinik' => 'pharmacy',
            ];

            $this->businessType = $aliases[$raw] ?? $raw;

            return $this->businessType;
        } catch (\Throwable) {
            return null;
        }
    }

    private function aboutsTableExists(): bool
    {
        return once(fn () => \Illuminate\Support\Facades\Schema::hasTable('abouts'));
    }

    private function isHiddenByNiche(string $navKey): bool
    {
        $bt = $this->getBusinessType();
        if (! $bt) {
            return false;
        }

        $nicheConfig = config("niches.nav.{$bt}.hidden", []);

        return in_array($navKey, $nicheConfig, true);
    }

    private function generateNavigationItem(string $resource, ?string $feature = null, ?array $activeWhen = [], ?string $nicheKey = null, bool $hideOnPremise = false): ?NavigationItem
    {
        $canAccess = $feature ? feature($feature) && $resource::canAccess() : $resource::canAccess();

        if ($nicheKey && $this->isHiddenByNiche($nicheKey)) {
            return null;
        }

        if ($hideOnPremise && config('app.on_premise')) {
            return null;
        }

        $active = false;
        if ((new $resource) instanceof Page) {
            $active = Str::of($resource::getRouteName())->exactly(Route::current()->getName());
        }

        if ((new $resource) instanceof Resource) {
            $active = Str::of(Route::currentRouteName())->contains($resource::getRouteBaseName());
        }

        if (count($activeWhen) > 0) {
            $activatedRoute = [];
            foreach ($activeWhen as $resourceClass) {
                $activatedRoute[] = $resourceClass::getRouteName();
            }
            $activatedRoute[] = $resource::getRouteName();
            $active = in_array(Route::current()->getName(), $activatedRoute);
        }

        $label = method_exists($resource, 'getNavigationLabel')
            ? $resource::getNavigationLabel()
            : (method_exists($resource, 'getLabel') ? $resource::getLabel() : class_basename($resource));
        if (! filled($label)) {
            $label = class_basename($resource);
        }

        return NavigationItem::make($label)
            ->visible($canAccess)
            ->icon($resource::getNavigationIcon())
            ->isActiveWhen(fn (): bool => $active)
            ->url(fn (): string => $resource::getUrl());
    }
}
