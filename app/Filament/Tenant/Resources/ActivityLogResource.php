<?php

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?string $pluralLabel = 'Activity Log';

    protected static ?string $slug = 'activity-log';

    protected static ?string $recordTitleAttribute = 'description';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Action')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('event')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('subject_id')
                    ->label('Subject'),
                TextColumn::make('causer.email')
                    ->label('By'),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\ActivityLogResource\Pages\ListActivities::route('/'),
        ];
    }
}
