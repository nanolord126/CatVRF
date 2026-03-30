<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = CosmeticProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationLabel = 'Косметика';

        protected static ?string $navigationGroup = 'Cosmetics';

        public static function form(Form $form): Form
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
                                ->label('SKU')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('brand')
                                ->label('Бренд')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Описание')
                                ->rows(3),
                        ])->columns(2),
                    Forms\Components\Section::make('Классификация')
                        ->schema([
                            Forms\Components\Select::make('category')
                                ->label('Категория')
                                ->options([
                                    'foundation' => 'Тональный крем',
                                    'lipstick' => 'Помада',
                                    'mascara' => 'Тушь',
                                    'eyeshadow' => 'Тени для век',
                                    'blush' => 'Румяна',
                                    'perfume' => 'Парфюм',
                                    'skincare' => 'Уход за кожей',
                                    'nail_polish' => 'Лак для ногтей',
                                ])
                                ->required(),
                            Forms\Components\Select::make('skin_type')
                                ->label('Тип кожи')
                                ->options([
                                    'all' => 'Все типы',
                                    'oily' => 'Жирная',
                                    'dry' => 'Сухая',
                                    'combination' => 'Комбинированная',
                                    'sensitive' => 'Чувствительная',
                                ]),
                            Forms\Components\Toggle::make('cruelty_free')
                                ->label('Не тестирована на животных'),
                            Forms\Components\Toggle::make('natural')
                                ->label('Натуральная'),
                        ])->columns(2),
                    Forms\Components\Section::make('Цена и запасы')
                        ->schema([
                            Forms\Components\TextInput::make('price')
                                ->label('Цена (копейки)')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('current_stock')
                                ->label('Текущие запасы')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('min_stock_threshold')
                                ->label('Минимум для пополнения')
                                ->numeric(),
                        ])->columns(3),
                    Forms\Components\Section::make('Рейтинг')
                        ->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг')
                                ->numeric(),
                            Forms\Components\TextInput::make('review_count')
                                ->label('Отзывов')
                                ->numeric(),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('brand')
                        ->label('Бренд')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('category')
                        ->label('Категория')
                        ->badge(),
                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->formatStateUsing(fn ($state) => '₽' . number_format($state / 100, 2)),
                    Tables\Columns\TextColumn::make('current_stock')
                        ->label('Запасы')
                        ->numeric(),
                    Tables\Columns\IconColumn::make('cruelty_free')
                        ->label('Не на животных')
                        ->boolean(),
                    Tables\Columns\TextColumn::make('rating')
                        ->label('★ Рейтинг')
                        ->numeric(decimalPlaces: 1),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('category')
                        ->label('Категория')
                        ->options([
                            'foundation' => 'Тональный крем',
                            'lipstick' => 'Помада',
                            'mascara' => 'Тушь',
                            'eyeshadow' => 'Тени для век',
                            'blush' => 'Румяна',
                            'perfume' => 'Парфюм',
                            'skincare' => 'Уход за кожей',
                            'nail_polish' => 'Лак для ногтей',
                        ]),
                    Tables\Filters\SelectFilter::make('brand')
                        ->label('Бренд'),
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

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListCosmeticProducts::route('/'),
                'create' => Pages\CreateCosmeticProduct::route('/create'),
                'edit' => Pages\EditCosmeticProduct::route('/{record}/edit'),
            ];
        }
}
