<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pet;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;

final class PetClinicResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Pet';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('address')->required(),
                Forms\Components\TextInput::make('phone')->tel()->required(),
                Forms\Components\TextInput::make('rating')->numeric(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('address')->sortable(),
            Tables\Columns\TextColumn::make('phone'),
            Tables\Columns\TextColumn::make('rating')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Tenant\Resources\Pet\PetClinicResource\Pages\ListPetClinics::route('/')];
    }
}
