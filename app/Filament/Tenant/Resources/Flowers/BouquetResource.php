<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\Bouquet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BouquetResource extends Resource
{
    protected static ?string $model = Bouquet::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Flowers';
    protected static ?string $navigationLabel = 'Bouquets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')
                ->required(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->maxLength(65535),
            Forms\Components\TextInput::make('price')
                ->required()
                ->numeric()
                ->prefix('₽'),
            Forms\Components\TextInput::make('stock_quantity')
                ->required()
                ->numeric(),
            Forms\Components\FileUpload::make('image_url')
                ->image(),
            Forms\Components\Toggle::make('is_available')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('stock_quantity'),
                Tables\Columns\ToggleColumn::make('is_available'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBouquets::route('/'),
            'create' => Pages\CreateBouquet::route('/create'),
            'edit' => Pages\EditBouquet::route('/{record}/edit'),
        ];
    }
}
