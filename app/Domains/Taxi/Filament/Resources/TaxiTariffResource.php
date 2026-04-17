<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources;

use App\Domains\Taxi\Models\TaxiTariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class TaxiTariffResource extends Resource
{
    protected static ?string $model = TaxiTariff::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Taxi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Код')
                            ->required()
                            ->unique(),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                        Forms\Components\Select::make('vehicle_class')
                            ->label('Класс авто')
                            ->options([
                                'economy' => 'Эконом',
                                'comfort' => 'Комфорт',
                                'comfort_plus' => 'Комфорт+',
                                'business' => 'Бизнес',
                                'premium' => 'Премиум',
                                'van' => 'Минивэн',
                                'cargo' => 'Грузовой',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('icon')
                            ->label('Иконка (emoji)')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('color')
                            ->label('Цвет (hex)')
                            ->maxLength(7),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Ценообразование')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Базовая цена (коп.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('price_per_km')
                            ->label('Цена за км (коп.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('price_per_minute')
                            ->label('Цена за минуту (коп.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('minimum_price')
                            ->label('Минимальная цена (коп.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('waiting_price_per_minute')
                            ->label('Ожидание (коп./мин)')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_surge_multiplier')
                            ->label('Макс. surge множитель')
                            ->numeric()
                            ->default(3.0),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Фичи')
                    ->schema([
                        Forms\Components\Toggle::make('fixed_price_available')
                            ->label('Фиксированная цена'),
                        Forms\Components\Toggle::make('preorder_available')
                            ->label('Предзаказ'),
                        Forms\Components\Toggle::make('split_payment_available')
                            ->label('Split payment'),
                        Forms\Components\Toggle::make('corporate_payment_available')
                            ->label('Корпоративная оплата'),
                        Forms\Components\Toggle::make('voice_order_available')
                            ->label('Голосовой заказ'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Требования к авто')
                    ->schema([
                        Forms\Components\TextInput::make('min_vehicle_year')
                            ->label('Мин. год авто')
                            ->numeric(),
                        Forms\Components\TextInput::make('min_vehicle_rating')
                            ->label('Мин. рейтинг авто')
                            ->numeric(),
                        Forms\Components\TextInput::make('passenger_capacity')
                            ->label('Вместимость пассажиров')
                            ->numeric(),
                        Forms\Components\TextInput::make('luggage_capacity')
                            ->label('Вместимость багажа')
                            ->numeric(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('B2B тарифы')
                    ->schema([
                        Forms\Components\Toggle::make('b2b_enabled')
                            ->label('B2B включен'),
                        Forms\Components\TextInput::make('b2b_discount_percentage')
                            ->label('Скидка (%)')
                            ->numeric(),
                        Forms\Components\TextInput::make('b2b_monthly_limit')
                            ->label('Лимит (коп./мес)')
                            ->numeric(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_class')
                    ->label('Класс авто')
                    ->badge(),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Базовая цена')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('current_surge_multiplier')
                    ->label('Surge')
                    ->suffix('x'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_class')
                    ->options([
                        'economy' => 'Эконом',
                        'comfort' => 'Комфорт',
                        'comfort_plus' => 'Комфорт+',
                        'business' => 'Бизнес',
                        'premium' => 'Премиум',
                        'van' => 'Минивэн',
                        'cargo' => 'Грузовой',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTaxiTariffs::route('/'),
            'create' => Pages\CreateTaxiTariff::route('/create'),
            'view' => Pages\ViewTaxiTariff::route('/{record}'),
            'edit' => Pages\EditTaxiTariff::route('/{record}/edit'),
        ];
    }
}
