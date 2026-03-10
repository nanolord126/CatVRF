<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\RestaurantMenuResource\Pages;
use App\Models\Tenants\RestaurantMenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantMenuResource extends Resource
{
    protected static ?string $model = RestaurantMenuItem::class;
    protected static ?string $navigationGroup = 'Marketplace';
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
                Forms\Components\TextInput::make('category'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category')->sortable(),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantMenu::route('/'),
            'create' => Pages\CreateRestaurantMenu::route('/create'),
            'edit' => Pages\EditRestaurantMenu::route('/{record}/edit'),
        ];
    }
}
