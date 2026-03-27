<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\AutoPart;
use App\Filament\Tenant\Resources\AutoPartResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: AutoPartResource.
 * Управление складом запчастей (СТО).
 */
final class AutoPartResource extends Resource
{
    protected static ?string $model = AutoPart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationGroup = 'Автосервис (СТО)';

    protected static ?string $label = 'Запчасть';
    
    protected static ?string $pluralLabel = 'Склад запчастей';

    protected static ?string $slug = 'auto/parts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о запчасти')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('SKU / Артикул'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Наименование'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Бренд'),
                        Forms\Components\TextInput::make('price_kopecks')
                            ->numeric()
                            ->required()
                            ->label('Цена (коп)'),
                    ])->columns(2),

                Forms\Components\Section::make('Складские остатки')
                    ->schema([
                        Forms\Components\TextInput::make('current_stock')
                            ->numeric()
                            ->default(0)
                            ->label('В наличии'),
                        Forms\Components\TextInput::make('min_stock_threshold')
                            ->numeric()
                            ->default(5)
                            ->label('Мин. порог'),
                    ])->columns(2),
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
                    ->label('Наименование'),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Бренд'),
                Tables\Columns\TextColumn::make('price_kopecks')
                    ->money('rub', shouldConvert: true)
                    ->label('Цена'),
                Tables\Columns\BadgeColumn::make('current_stock')
                    ->color(fn ($state, $record) => $state <= $record->min_stock_threshold ? 'danger' : 'success')
                    ->label('Остаток'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutoParts::route('/'),
            'create' => Pages\CreateAutoPart::route('/create'),
            'edit' => Pages\EditAutoPart::route('/{record}/edit'),
        ];
    }
}
