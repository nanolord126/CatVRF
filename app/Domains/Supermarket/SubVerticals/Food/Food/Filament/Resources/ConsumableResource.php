<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\Food\Filament\Resources;

use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Domains\Food\Filament\Resources\ConsumableResource\Pages\CreateConsumable;
use App\Domains\Food\Filament\Resources\ConsumableResource\Pages\EditConsumable;
use App\Domains\Food\Filament\Resources\ConsumableResource\Pages\ListConsumables;

final class ConsumableResource extends BaseOptimizedResource
{
    protected static ?string $model = FoodConsumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Food & Delivery';

    protected static ?string $label = 'Расходник';

    protected static ?string $pluralLabel = 'Расходники';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        TextInput::make('unit')
                            ->label('Единица')
                            ->required(),
                        TextInput::make('current_stock')
                            ->label('Текущий остаток')
                            ->numeric()
                            ->required(),
                        TextInput::make('min_stock_threshold')
                            ->label('Минимум')
                            ->numeric()
                            ->required(),
                        TextInput::make('price')
                            ->label('Цена за единицу')
                            ->numeric(),
                    ]),
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
                TextColumn::make('unit')
                    ->label('Единица'),
                TextColumn::make('current_stock')->badge()
                    ->label('Остаток')
                    ->sortable(),
                TextColumn::make('current_stock')->badge()
                    ->label('Статус')
                    ->getStateUsing(function (FoodConsumable $record): string {
                        return $record->current_stock < $record->min_stock_threshold ? 'Мало' : 'Ок';
                    })
                    ->colors([
                        'danger' => 'Мало',
                        'success' => 'Ок',
                    ]),
                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', 100),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsumables::route('/'),
            'create' => CreateConsumable::route('/create'),
            'edit' => EditConsumable::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for Food
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}
