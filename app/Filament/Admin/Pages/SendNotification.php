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

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Send Notification';

    protected static ?string $slug = 'send-notification';

    protected static string $view = 'filament.admin.pages.send-notification';

    public ?string $target = null;

    public ?string $subject = null;

    public ?string $body = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function send(): void
    {
        $data = $this->form->getState();
        $target = $data['target'];
        $subject = $data['subject'];
        $body = $data['body'];

        $tenants = $target === 'all'
            ? Tenant::where('is_active', true)->get()
            : Tenant::where('id', $target)->get();

        $count = 0;
        foreach ($tenants as $tenant) {
            // Send notification inside each tenant's database
            $tenant->run(function () use ($subject, $body, &$count) {
                $users = \App\Models\Tenants\User::all();
                foreach ($users as $user) {
                    $user->notify(new BroadcastMessage($subject, $body));
                    $count++;
                }
            });
        }

        Notification::make()
            ->success()
            ->title("Notification sent to {$count} user(s) across {$tenants->count()} tenant(s)")
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
                    ->rows(6),
            ]);
    }
}
