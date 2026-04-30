<?php

declare(strict_types=1);

namespace Modules\Warehouse\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Warehouse\Infrastructure\Models\ProductModel;

class ProductResource extends Resource
{
    protected static ?string $model = ProductModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->maxLength(100)
                            ->unique()
                            ->label('SKU'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Product Name'),
                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(100)
                            ->unique()
                            ->nullable()
                            ->label('Barcode'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->nullable()
                            ->label('Description'),
                        Forms\Components\TextInput::make('unit')
                            ->default('шт')
                            ->maxLength(20)
                            ->label('Unit'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Physical Properties')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.001)
                            ->default(0)
                            ->label('Weight'),
                        Forms\Components\TextInput::make('weight_unit')
                            ->default('kg')
                            ->maxLength(20)
                            ->label('Weight Unit'),
                        Forms\Components\KeyValue::make('dimensions')
                            ->keyLabel('Dimension')
                            ->valueLabel('Value')
                            ->nullable()
                            ->label('Dimensions'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\TextInput::make('category')
                            ->maxLength(100)
                            ->nullable()
                            ->label('Category'),
                        Forms\Components\TextInput::make('brand')
                            ->maxLength(100)
                            ->nullable()
                            ->label('Brand'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Special Requirements')
                    ->schema([
                        Forms\Components\Toggle::make('is_hazardous')
                            ->label('Hazardous Material'),
                        Forms\Components\Toggle::make('is_fragile')
                            ->label('Fragile'),
                        Forms\Components\Toggle::make('requires_temperature_control')
                            ->label('Temperature Control'),
                        Forms\Components\TextInput::make('min_temperature')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->label('Min Temperature (°C)'),
                        Forms\Components\TextInput::make('max_temperature')
                            ->numeric()
                            ->step(0.01)
                            ->nullable()
                            ->label('Max Temperature (°C)'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable()
                    ->label('Barcode'),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->toggleable()
                    ->label('Category'),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->toggleable()
                    ->label('Brand'),
                Tables\Columns\IconColumn::make('is_hazardous')
                    ->boolean()
                    ->color('danger')
                    ->toggleable()
                    ->label('Hazardous'),
                Tables\Columns\IconColumn::make('is_fragile')
                    ->boolean()
                    ->color('warning')
                    ->toggleable()
                    ->label('Fragile'),
                Tables\Columns\IconColumn::make('requires_temperature_control')
                    ->boolean()
                    ->color('info')
                    ->toggleable()
                    ->label('Temp Control'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(function () {
                        return ProductModel::select('category')
                            ->distinct()
                            ->whereNotNull('category')
                            ->pluck('category', 'category')
                            ->toArray();
                    })
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('brand')
                    ->options(function () {
                        return ProductModel::select('brand')
                            ->distinct()
                            ->whereNotNull('brand')
                            ->pluck('brand', 'brand')
                            ->toArray();
                    })
                    ->label('Brand'),
                Tables\Filters\TernaryFilter::make('is_hazardous')
                    ->label('Hazardous'),
                Tables\Filters\TernaryFilter::make('is_fragile')
                    ->label('Fragile'),
                Tables\Filters\TernaryFilter::make('requires_temperature_control')
                    ->label('Temp Control'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => \Modules\Warehouse\Filament\Resources\ProductResource\Pages\ListProducts::route('/'),
            'create' => \Modules\Warehouse\Filament\Resources\ProductResource\Pages\CreateProduct::route('/create'),
            'view' => \Modules\Warehouse\Filament\Resources\ProductResource\Pages\ViewProduct::route('/{record}'),
            'edit' => \Modules\Warehouse\Filament\Resources\ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
