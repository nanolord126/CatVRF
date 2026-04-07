<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Filament\Resources;

use App\Domains\Inventory\Enums\InventoryCheckStatus;
use App\Domains\Inventory\Filament\Resources\InventoryCheckResource\Pages;
use App\Domains\Inventory\Models\InventoryCheck;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Filament Resource для управления инвентаризациями.
 *
 * Tenant-scoped через глобальный scope на модели.
 */
final class InventoryCheckResource extends Resource
{
    protected static ?string $model = InventoryCheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Инвентаризации';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('warehouse_id')
                ->label('Склад ID')
                ->required()
                ->numeric(),

            TextInput::make('employee_id')
                ->label('Сотрудник ID')
                ->required()
                ->numeric(),

            Select::make('status')
                ->label('Статус')
                ->options(
                    collect(InventoryCheckStatus::cases())
                        ->mapWithKeys(fn (InventoryCheckStatus $s) => [$s->value => $s->value])
                        ->toArray(),
                )
                ->required(),

            Textarea::make('comments')
                ->label('Комментарии')
                ->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label('ID')
                ->sortable(),

            TextColumn::make('warehouse_id')
                ->label('Склад')
                ->sortable(),

            TextColumn::make('employee_id')
                ->label('Сотрудник')
                ->sortable(),

            TextColumn::make('status')
                ->label('Статус')
                ->badge()
                ->sortable(),

            TextColumn::make('correlation_id')
                ->label('Correlation ID')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->label('Создано')
                ->dateTime()
                ->sortable(),
        ]);
    }

    /** @return array<string, string> */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInventoryChecks::route('/'),
            'create' => Pages\CreateInventoryCheck::route('/create'),
            'edit'   => Pages\EditInventoryCheck::route('/{record}/edit'),
        ];
    }
}
