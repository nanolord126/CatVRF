<?php declare(strict_types=1);

namespace App\Domains\Taxi\Filament\Resources;

use App\Domains\Taxi\Models\TaxiRide;
use Filament\Forms;
use Filament\Forms\Form;
use App\Domains\Taxi\Filament\Resources\TaxiRideResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TaxiRideResource extends Resource
{
    protected static ?string $model = TaxiRide::class;

    protected static ?string $navigationIcon = 'heroicon-o-car';

    protected static ?string $navigationGroup = 'Taxi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('passenger_id')
                            ->label('Пассажир')
                            ->relationship('passenger', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('driver_id')
                            ->label('Водитель')
                            ->relationship('driver', 'name')
                            ->searchable(),
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'plate_number')
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Поиск водителя',
                                'driver_assigned' => 'Водитель найден',
                                'in_progress' => 'В поездке',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                                'no_drivers_available' => 'Водителей нет',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Маршрут')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_address')
                            ->label('Адрес посадки')
                            ->required(),
                        Forms\Components\TextInput::make('dropoff_address')
                            ->label('Адрес назначения')
                            ->required(),
                        Forms\Components\TextInput::make('distance_km')
                            ->label('Расстояние (км)')
                            ->numeric(),
                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('Ожидаемое время (мин)')
                            ->numeric(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Ценообразование')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Стоимость (коп.)')
                            ->numeric(),
                        Forms\Components\TextInput::make('final_price')
                            ->label('Финальная стоимость (коп.)')
                            ->numeric(),
                        Forms\Components\TextInput::make('surge_multiplier')
                            ->label('Surge множитель')
                            ->numeric(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Способ оплаты')
                            ->options([
                                'wallet' => 'Кошелек',
                                'card' => 'Карта',
                                'cash' => 'Наличные',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('passenger.name')
                    ->label('Пассажир')
                    ->searchable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Водитель')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.plate_number')
                    ->label('Автомобиль')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'driver_assigned' => 'info',
                        'in_progress' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Стоимость')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Поиск водителя',
                        'driver_assigned' => 'Водитель найден',
                        'in_progress' => 'В поездке',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
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

    public static function getRelations(): array
    {
        return [
            'passenger',
            'driver',
            'vehicle',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiRides::route('/'),
            'create' => Pages\CreateTaxiRide::route('/create'),
            'view' => Pages\ViewTaxiRide::route('/{record}'),
            'edit' => Pages\EditTaxiRide::route('/{record}/edit'),
        ];
    }
}
