<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\RoleResource\Pages;
use App\Models\Tenants\Role;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    use HasTranslatableResource;

    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->translateLabel()
                    ->required(),
                ...static::buildPermissionSections('web', 'Web App Permissions'),
                ...static::buildPermissionSections('sanctum', 'Mobile App Permissions'),
            ])->columns(1);
    }

    /**
     * Build Section-wrapped CheckboxList components for a given guard.
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected static function buildPermissionSections(string $guard, string $heading): array
    {
        $sections = [];

        foreach (Role::PERMISSION_GROUPS as $groupKey => $group) {
            $permissionNames = $group[$guard] ?? [];

            if (empty($permissionNames)) {
                continue;
            }

            $methodName = $guard . ucfirst($groupKey) . 'Permissions';

            $sections[] = Section::make($group['label'])
                ->icon($group['icon'])
                ->collapsible()
                ->collapsed()
                ->schema([
                    CheckboxList::make($methodName)
                        ->label(false)
                        ->bulkToggleable()
                        ->columns(3)
                        ->relationship(
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->whereIn(
                                config('permission.table_names.permissions').'.name',
                                $permissionNames
                            )
                        )
                        ->noSearchResultsMessage(__('No permissions found.'))
                        ->searchable(),
                ]);
        }

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->translateLabel()
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'create' => Pages\CreateRole::route('/create'),
        ];
    }
}
