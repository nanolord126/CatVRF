<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->unique(),
            Forms\Components\Select::make('permissions')
                ->multiple()->relationship('permissions', 'name')->preload(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('permissions.name')->badge(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }
}
