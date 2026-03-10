<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\ProductResource\Pages;
use Modules\Inventory\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Product Information')
                    ->schema([
                        Components\TextInput::make('name')->required(),
                        Components\TextInput::make('sku')->required()->unique(ignoreRecord: true),
                        Components\TextInput::make('category'),
                        Components\Select::make('unit')
                            ->options(['pcs' => 'Pcs', 'ml' => 'Ml', 'g' => 'G'])
                            ->default('pcs'),
                        Components\TextInput::make('price')->numeric(),
                        Components\Toggle::make('is_consumable')->default(false),
                    ])->columns(2),
                Components\Section::make('Stock Management')
                    ->schema([
                        Components\TextInput::make('stock')->numeric()->disabled()->default(0), // Manual updates discouraged
                        Components\TextInput::make('min_stock')->numeric()->default(0),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')->searchable()->sortable(),
                Columns\TextColumn::make('sku')->searchable(),
                Columns\TextColumn::make('category')->sortable(),
                Columns\TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn (Product $record) => match (true) {
                        $record->stock < 10 => 'danger',
                        $record->stock < 100 => 'warning',
                        $record->stock < 500 => 'success',
                        default => 'info',
                    })
                    ->icon(fn (Product $record) => $record->stock >= 500 ? 'heroicon-m-bolt' : null)
                    ->description(fn (Product $record) => $record->stock <= $record->min_stock ? 'Низкий остаток!' : ''),
                Columns\TextColumn::make('unit'),
                Columns\IconColumn::make('is_consumable')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ProductResource\Pages\ListProducts::route('/'),
            'create' => ProductResource\Pages\CreateProduct::route('/create'),
            'edit' => ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
