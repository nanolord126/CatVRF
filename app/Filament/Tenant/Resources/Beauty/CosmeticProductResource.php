<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\CosmeticProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class CosmeticProductResource extends Resource
{
    protected static ?string $model = CosmeticProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Beauty';
    protected static ?string $navigationLabel = 'Cosmetic Products';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('salon_id')
                ->relationship('salon', 'name')
                ->required(),
            Forms\Components\TextInput::make('brand')->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('category')->required(),
            Forms\Components\TextInput::make('volume'),
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->prefix('₽')
                ->required(),
            Forms\Components\TextInput::make('stock')->numeric()->required(),
            Forms\Components\Toggle::make('is_available')->default(true),
            Forms\Components\Toggle::make('is_professional')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('brand'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB', divideBy: 1),
                Tables\Columns\TextColumn::make('stock'),
                Tables\Columns\ToggleColumn::make('is_available'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCosmeticProducts::route('/'),
            'create' => Pages\CreateCosmeticProduct::route('/create'),
            'edit' => Pages\EditCosmeticProduct::route('/{record}/edit'),
        ];
    }
}
