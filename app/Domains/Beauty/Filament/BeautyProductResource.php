<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use App\Domains\Beauty\Models\BeautyProduct;
use Filament\Resources\Resource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, TextInput, Select, Textarea};
use Filament\Tables\Columns\{TextColumn, NumberColumn, BadgeColumn};
use Filament\Tables\Actions\{DeleteAction, EditAction};
use Filament\Tables\Filters\{Filter, SelectFilter};

/**
 * Filament Resource для товаров красоты.
 * Production 2026.
 */
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
                BadgeColumn::make('consumable_type')
                    ->label('Тип')
                    ->getStateUsing(fn ($record) => match ($record->consumable_type) {
                        'low' => 'Низкий',
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
