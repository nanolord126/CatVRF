<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use App\Domains\Auto\Models\AutoPart;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class AutoPartResource extends Resource
{
    protected static ?string $model = AutoPart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Автосервис';

    protected static ?string $label = 'Запчасть';

    protected static ?string $pluralLabel = 'Запчасти';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->label('Артикул (SKU)')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('oem_number')
                            ->label('OEM Номер')
                            ->maxLength(100),
                        Forms\Components\Select::make('auto_catalog_brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Цены и Склад')
                    ->schema([
                        Forms\Components\TextInput::make('price_kopecks')
                            ->label('Розничная цена (коп)')
                            ->numeric()
                            ->required()
                            ->suffix('коп'),
                        Forms\Components\TextInput::make('wholesale_price_kopecks')
                            ->label('Оптовая цена (коп)')
                            ->numeric()
                            ->required()
                            ->suffix('коп'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Остаток на складе')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('min_threshold')
                            ->label('Минимальный порог')
                            ->numeric()
                            ->default(5)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Совместимость и Теги')
                    ->schema([
                        Forms\Components\TagsInput::make('compatibility_vin')
                            ->label('Совместимые VIN (маски)')
                            ->placeholder('Добавить VIN'),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Склад')
                    ->numeric()
                    ->badge()
                    ->color(fn (AutoPart $record): string => $record->isLowStock() ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('auto_catalog_brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ListAutoParts::route('/'),
            'create' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\CreateAutoPart::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\ViewAutoPart::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Auto\AutoPartResource\Pages\EditAutoPart::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}
