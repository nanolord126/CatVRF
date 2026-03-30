<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DishResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Dish::class;

        protected static ?string $navigationIcon = 'heroicon-o-fire';

        protected static ?string $navigationGroup = 'Food & Delivery';

        protected static ?string $label = 'Блюдо';

        protected static ?string $pluralLabel = 'Блюда';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->schema([
                            TextInput::make('name')
                                ->label('Название')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label('Описание')
                                ->maxLength(1000),
                            TextInput::make('price')
                                ->label('Цена')
                                ->required()
                                ->numeric(),
                            TextInput::make('calories')
                                ->label('Калории')
                                ->numeric(),
                            TextInput::make('cooking_time_minutes')
                                ->label('Время готовки (мин)')
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
                    TextColumn::make('price')
                        ->label('Цена')
                        ->money('RUB', 100)
                        ->sortable(),
                    TextColumn::make('calories')
                        ->label('Калории')
                        ->sortable(),
                    TextColumn::make('cooking_time_minutes')
                        ->label('Время готовки')
                        ->suffix(' мин'),
                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime()
                        ->sortable(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Food\Filament\Resources\DishResource\Pages\ListDishes::route('/'),
                'create' => \App\Domains\Food\Filament\Resources\DishResource\Pages\CreateDish::route('/create'),
                'edit' => \App\Domains\Food\Filament\Resources\DishResource\Pages\EditDish::route('/{record}/edit'),
            ];
        }
}
