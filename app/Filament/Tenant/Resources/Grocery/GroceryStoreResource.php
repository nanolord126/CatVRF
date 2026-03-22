<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Grocery;

use App\Domains\Grocery\Models\GroceryStore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class GroceryStoreResource extends Resource
{
    protected static ?string $model = GroceryStore::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Grocery';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\Select::make('store_type')
                ->options([
                    'supermarket' => 'Supermarket',
                    'vegetable' => 'Vegetable Shop',
                    'meat' => 'Meat Shop',
                    'cafe' => 'Cafe/Restaurant',
                ])
                ->required(),
            Forms\Components\TextInput::make('kitchen_type'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('store_type'),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\ToggleColumn::make('is_active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroceryStores::route('/'),
            'create' => Pages\CreateGroceryStore::route('/create'),
            'edit' => Pages\EditGroceryStore::route('/{record}/edit'),
        ];
    }
}
