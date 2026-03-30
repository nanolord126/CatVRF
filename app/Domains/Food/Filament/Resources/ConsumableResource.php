<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConsumableResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                    TextColumn::make('current_stock')
                        ->label('Остаток')
                        ->sortable(),
                    BadgeColumn::make('current_stock')
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
                'index' => \App\Domains\Food\Filament\Resources\ConsumableResource\Pages\ListConsumables::route('/'),
                'create' => \App\Domains\Food\Filament\Resources\ConsumableResource\Pages\CreateConsumable::route('/create'),
                'edit' => \App\Domains\Food\Filament\Resources\ConsumableResource\Pages\EditConsumable::route('/{record}/edit'),
            ];
        }
}
