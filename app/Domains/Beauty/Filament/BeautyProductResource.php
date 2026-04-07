<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use App\Domains\Beauty\Models\BeautyProduct;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\NumberColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class BeautyProductResource extends Resource
{
    protected static ?string $model = BeautyProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $pluralModelLabel = 'Товары красоты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основное')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('sku')
                        ->label('SKU')
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label('Описание'),
                    Select::make('consumable_type')
                        ->label('Тип расхода')
                        ->options([
                            'none' => 'Не расходник',
                                'low' => 'Низкий расход',
                                'medium' => 'Средний расход',
                                'high' => 'Высокий расход',
                            ])
                            ->default('none'),
                    ])
                    ->columns(2),
                Section::make('Остатки')
                    ->schema([
                        TextInput::make('current_stock')
                            ->label('Текущий остаток')
                            ->numeric()
                            ->required(),
                        TextInput::make('min_stock_threshold')
                            ->label('Минимальный порог')
                            ->numeric(),
                        TextInput::make('price')
                            ->label('Цена (копейки)')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(3),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('sku')
                        ->label('SKU')
                        ->searchable(),
                    NumberColumn::make('current_stock')
                        ->label('Остаток')
                        ->sortable(),
                    TextColumn::make('consumable_type')
                        ->badge()
                        ->label('Тип')
                        ->getStateUsing(fn ($record) => match ($record->consumable_type) {
                            'medium' => 'Средний',
                            'high' => 'Высокий',
                            default => 'Нет',
                        }),
                ])
                ->filters([
                    SelectFilter::make('consumable_type')
                        ->label('Тип расхода')
                        ->options([
                            'none' => 'Не расходник',
                            'low' => 'Низкий расход',
                            'medium' => 'Средний расход',
                            'high' => 'Высокий расход',
                        ]),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    // Bulk actions here
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => ListRecords::class,
                'create' => CreateRecord::class,
                'edit' => EditRecord::class,
            ];
        }
}
