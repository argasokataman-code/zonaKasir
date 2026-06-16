<?php

namespace App\Filament\Admin\Pages;

use App\Notifications\BroadcastMessage;
use App\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Facades\Activity;

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Send Notification';

    protected static ?string $slug = 'send-notification';

    protected static string $view = 'filament.admin.pages.send-notification';

    protected static ?string $title = 'Send Notification';

    public ?string $target = null;

    public ?string $subject = null;

    public ?string $body = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('manage settings') ?? false;
    }

    public function send(): void
    {
        // Rate limit: max 5 sends per minute per admin
        $adminId = auth('admin')->id();
        $rateKey = "send-notification:{$adminId}";

        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);
            Notification::make()
                ->danger()
                ->title('Too many requests')
                ->body("Please wait {$seconds} seconds before sending again.")
                ->send();
            return;
        }

        RateLimiter::hit($rateKey, 60);

        $data = $this->form->getState();
        $target = $data['target'];
        $subject = $data['subject'];
        $body = $data['body'];

        $tenants = $target === 'all'
            ? Tenant::where('is_active', true)->get()
            : Tenant::where('id', $target)->get();

        if ($tenants->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('No tenants found')
                ->send();
            return;
        }

        $totalCount = 0;
        $failedTenants = [];

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($subject, $body, &$totalCount) {
                    \App\Models\Tenants\User::query()
                        ->chunkById(200, function ($users) use ($subject, $body, &$totalCount) {
                            foreach ($users as $user) {
                                try {
                                    $user->notify(new BroadcastMessage($subject, $body));
                                    $totalCount++;
                                } catch (\Exception $e) {
                                    \Log::warning("Failed to send notification to user {$user->id}: {$e->getMessage()}");
                                }
                            }
                        });
                });
            } catch (\Exception $e) {
                $failedTenants[] = $tenant->id;
                \Log::error("Failed to send notification to tenant {$tenant->id}: {$e->getMessage()}");
            }
        }

        // Audit log
        Activity::causedBy(auth('admin')->user())
            ->performedOn(Tenant::class)
            ->event('send_notification')
            ->log("Sent notification to {$totalCount} user(s) across {$tenants->count()} tenant(s). Subject: {$subject}");

        $message = "Notification sent to {$totalCount} user(s) across {$tenants->count()} tenant(s)";
        if (! empty($failedTenants)) {
            $message .= ". Failed: " . implode(', ', $failedTenants);
        }

        Notification::make()
            ->success()
            ->title($message)
            ->send();

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $tenantOptions = Tenant::where('is_active', true)
            ->get()
            ->pluck('data.full_name', 'id')
            ->mapWithKeys(fn ($name, $id) => [$id => "{$name} ({$id})"])
            ->toArray();

        return $form
            ->schema([
                Select::make('target')
                    ->label('Send to')
                    ->options(['all' => 'All Active Tenants'] + $tenantOptions)
                    ->searchable()
                    ->required(),
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->required()
                    ->maxLength(5000)
                    ->rows(6),
            ]);
    }
}
