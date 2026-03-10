<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\RestaurantDishResource\Pages;
use App\Models\Tenants\RestaurantDish;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantDishResource extends Resource
{
    protected static ?string $model = RestaurantDish::class;
    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static ?string $navigationGroup = '🛒 Marketplace';
    protected static ?string $modelLabel = 'Блюдо';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')->relationship('category', 'name')->required()->label('Категория'),
                Forms\Components\TextInput::make('name')->required()->label('Название'),
                Forms\Components\Textarea::make('description')->label('Описание'),
                Forms\Components\TextInput::make('price')->numeric()->prefix('₽')->required()->label('Цена'),
                Forms\Components\FileUpload::make('photo_url')->image()->label('Фото'),
                Forms\Components\Toggle::make('is_available')->default(true)->label('Доступно'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')->label('Фото'),
                Tables\Columns\TextColumn::make('name')->searchable()->label('Название'),
                Tables\Columns\TextColumn::make('category.name')->label('Категория'),
                Tables\Columns\TextColumn::make('price')->money('RUB')->label('Цена'),
                Tables\Columns\IconColumn::make('is_available')->boolean()->label('Доступно'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantDishes::route('/'),
            'create' => Pages\CreateRestaurantDish::route('/create'),
            'edit' => Pages\EditRestaurantDish::route('/{record}/edit'),
        ];
    }
}
