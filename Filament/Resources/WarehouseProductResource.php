<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseProductResource\Pages;
use App\Models\WarehouseProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WarehouseProductResource extends Resource
{
    protected static ?string $model = WarehouseProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Warehouse';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->unique()
                            ->label('SKU'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Product Name'),
                        Forms\Components\TextInput::make('barcode')
                            ->unique()
                            ->label('Barcode'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description'),
                        Forms\Components\TextInput::make('category')
                            ->label('Category'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Brand'),
                    ]),

                Forms\Components\Section::make('Physical Properties')
                    ->schema([
                        Forms\Components\TextInput::make('unit')
                            ->default('шт')
                            ->label('Unit'),
                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->step(0.001)
                            ->label('Weight'),
                        Forms\Components\Select::make('weight_unit')
                            ->options([
                                'kg' => 'Kilograms',
                                'g' => 'Grams',
                                'lb' => 'Pounds',
                            ])
                            ->default('kg')
                            ->label('Weight Unit'),
                        Forms\Components\KeyValue::make('dimensions')
                            ->label('Dimensions'),
                    ]),

                Forms\Components\Section::make('Storage Requirements')
                    ->schema([
                        Forms\Components\Toggle::make('is_hazardous')
                            ->label('Hazardous Material'),
                        Forms\Components\Toggle::make('is_fragile')
                            ->label('Fragile'),
                        Forms\Components\Toggle::make('requires_temperature_control')
                            ->label('Requires Temperature Control')
                            ->reactive(),
                        Forms\Components\TextInput::make('min_temperature')
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn (Forms\Get $get) => $get('requires_temperature_control'))
                            ->label('Min Temperature (°C)'),
                        Forms\Components\TextInput::make('max_temperature')
                            ->numeric()
                            ->step(0.01)
                            ->visible(fn (Forms\Get $get) => $get('requires_temperature_control'))
                            ->label('Max Temperature (°C)'),
                    ]),

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
                    ->label('Barcode'),
                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->label('Category'),
                Tables\Columns\IconColumn::make('is_hazardous')
                    ->boolean()
                    ->label('Hazardous'),
                Tables\Columns\IconColumn::make('requires_temperature_control')
                    ->boolean()
                    ->label('Temp Control'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\TernaryFilter::make('is_hazardous')
                    ->label('Hazardous'),
                Tables\Filters\TernaryFilter::make('requires_temperature_control')
                    ->label('Temp Control'),
            ])
            ->actions([
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
            'index' => Pages\ListWarehouseProducts::route('/'),
            'create' => Pages\CreateWarehouseProduct::route('/create'),
            'edit' => Pages\EditWarehouseProduct::route('/{record}/edit'),
        ];
    }
}
