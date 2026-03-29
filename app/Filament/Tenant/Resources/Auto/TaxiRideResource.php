<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use App\Domains\Taxi\Models\TaxiRide;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class TaxiRideResource extends Resource
{
    protected static ?string $model = TaxiRide::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right';

    protected static ?string $navigationGroup = 'Auto';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация о заказе')
                ->icon('heroicon-m-ticket')
                ->description('Основные данные поездки')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn () => Str::uuid())
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('ride_number')
                        ->label('Номер поездки')
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\Select::make('driver_id')
                        ->label('Водитель')
                        ->relationship('driver', 'full_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Автомобиль')
                        ->relationship('vehicle', 'license_plate')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Select::make('passenger_id')
                        ->label('Пассажир')
                        ->relationship('passenger', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpan(2),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'На ожидании',
                            'accepted' => 'Принята',
                            'driver_on_way' => 'Водитель в пути',
                            'arrived' => 'Водитель приехал',
                            'in_progress' => 'В пути',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                        ])
                        ->required()
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Маршрут')
                ->icon('heroicon-m-map')
                ->description('Точки маршрута и координаты')
                ->schema([
                    Forms\Components\TextInput::make('pickup_point')
                        ->label('Точка отправления')
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('dropoff_point')
                        ->label('Точка прибытия')
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('pickup_lat')
                        ->label('Широта (отправление)')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('pickup_lon')
                        ->label('Долгота (отправление)')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('dropoff_lat')
                        ->label('Широта (прибытие)')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('dropoff_lon')
                        ->label('Долгота (прибытие)')
                        ->numeric()
                        ->step(0.0001)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('distance_km')
                        ->label('Расстояние (км)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('estimated_duration_minutes')
                        ->label('Ожидаемое время (мин)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Цена и расчёты')
                ->icon('heroicon-m-banknote')
                ->description('Стоимость и коэффициенты')
                ->schema([
                    Forms\Components\TextInput::make('base_price')
                        ->label('Базовая цена (₽)')
                        ->numeric()
                        ->disabled()
                        ->suffix('₽')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('surge_multiplier')
                        ->label('Коэффициент surge')
                        ->numeric()
                        ->default(1.0)
                        ->step(0.1)
                        ->minValue(0.5)
                        ->maxValue(5.0)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('price')
                        ->label('Итоговая цена (₽)')
                        ->numeric()
                        ->disabled()
                        ->suffix('₽')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('commission_percent')
                        ->label('Комиссия платформы (%)')
                        ->numeric()
                        ->disabled()
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('driver_revenue')
                        ->label('Доход водителя (₽)')
                        ->numeric()
                        ->disabled()
                        ->suffix('₽')
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('platform_revenue')
                        ->label('Доход платформы (₽)')
                        ->numeric()
                        ->disabled()
                        ->suffix('₽')
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Время и контакты')
                ->icon('heroicon-m-clock')
                ->description('Сроки и информация о контактах')
                ->schema([
                    Forms\Components\DateTimePicker::make('requested_at')
                        ->label('Запрошена в')
                        ->native(false)
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('accepted_at')
                        ->label('Принята в')
                        ->native(false)
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('started_at')
                        ->label('Начата в')
                        ->native(false)
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('completed_at')
                        ->label('Завершена в')
                        ->native(false)
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('passenger_phone')
                        ->label('Телефон пассажира')
                        ->tel()
                        ->copyable()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('driver_phone')
                        ->label('Телефон водителя')
                        ->tel()
                        ->copyable()
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),

            Forms\Components\Section::make('Оценка и отзывы')
                ->icon('heroicon-m-star')
                ->description('Рейтинг поездки')
                ->schema([
                    Forms\Components\TextInput::make('passenger_rating')
                        ->label('Оценка от пассажира (1-5)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('driver_rating')
                        ->label('Оценка от водителя (1-5)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5)
                        ->columnSpan(2),

                    Forms\Components\Forms\Textarea::make('passenger_comment')
                        ->label('Комментарий пассажира')
                        ->maxLength(500)
                        ->columnSpan('full'),

                    Forms\Components\Forms\Textarea::make('driver_comment')
                        ->label('Комментарий водителя')
                        ->maxLength(500)
                        ->columnSpan('full'),
                ])->columns(4),

            Forms\Components\Section::make('Примечания')
                ->icon('heroicon-m-chat-bubble-bottom-center-text')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Примечания')
                        ->maxLength(1000)
                        ->columnSpan('full'),
                ])->columnSpan('full'),

            Forms\Components\Section::make('Служебная информация')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Forms\Components\Hidden::make('tenant_id')
                        ->default(fn () => tenant('id')),

                    Forms\Components\Hidden::make('correlation_id')
                        ->default(fn () => Str::uuid()),

                    Forms\Components\Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),

                    Forms\Components\TextInput::make('created_at')
                        ->label('Создана')
                        ->disabled()
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('updated_at')
                        ->label('Обновлена')
                        ->disabled()
                        ->columnSpan(2),
                ])->columns(4),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('ride_number')
                ->label('№ поездки')
                ->searchable()
                ->sortable()
                ->icon('heroicon-m-ticket')
                ->limit(20),

            Tables\Columns\TextColumn::make('driver.full_name')
                ->label('Водитель')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('passenger.name')
                ->label('Пассажир')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('pickup_point')
                ->label('Откуда')
                ->searchable()
                ->limit(30),

            Tables\Columns\TextColumn::make('dropoff_point')
                ->label('Куда')
                ->searchable()
                ->limit(30),

            Tables\Columns\TextColumn::make('distance_km')
                ->label('Расстояние')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' км' : '—')
                ->numeric()
                ->sortable(),

            Tables\Columns\BadgeColumn::make('status')
                ->label('Статус')
                ->formatStateUsing(fn ($state) => match($state) {
                    'pending' => 'На ожидании',
                    'accepted' => 'Принята',
                    'driver_on_way' => 'Водитель едет',
                    'arrived' => 'Приехал',
                    'in_progress' => 'В пути',
                    'completed' => 'Завершена',
                    'cancelled' => 'Отменена',
                    default => $state,
                })
                ->color(fn ($state) => match($state) {
                    'pending' => 'warning',
                    'accepted' => 'info',
                    'driver_on_way' => 'primary',
                    'arrived' => 'cyan',
                    'in_progress' => 'cyan',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('price')
                ->label('Цена')
                ->money('RUB', divideBy: 100)
                ->sortable(),

            Tables\Columns\TextColumn::make('surge_multiplier')
                ->label('Surge')
                ->formatStateUsing(fn ($state) => 'x' . number_format($state, 1))
                ->badge()
                ->color(fn ($state) => $state > 1.5 ? 'danger' : 'success')
                ->sortable(),

            Tables\Columns\TextColumn::make('passenger_rating')
                ->label('Оценка ⭐')
                ->formatStateUsing(fn ($state) => $state ? '★ ' . $state : '—')
                ->badge()
                ->color(fn ($state) => match(true) {
                    $state >= 4.5 => 'success',
                    $state >= 4 => 'info',
                    $state >= 3 => 'warning',
                    default => 'danger',
                })
                ->sortable(),

            Tables\Columns\TextColumn::make('requested_at')
                ->label('Запрошена')
                ->dateTime('d.m H:i')
                ->sortable(),

            Tables\Columns\TextColumn::make('completed_at')
                ->label('Завершена')
                ->dateTime('d.m H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Создана')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->label('Статус')
                ->options([
                    'pending' => 'На ожидании',
                    'accepted' => 'Принята',
                    'driver_on_way' => 'Водитель едет',
                    'arrived' => 'Приехал',
                    'in_progress' => 'В пути',
                    'completed' => 'Завершена',
                    'cancelled' => 'Отменена',
                ])
                ->multiple(),

            Tables\Filters\SelectFilter::make('driver_id')
                ->label('Водитель')
                ->relationship('driver', 'full_name')
                ->searchable()
                ->preload()
                ->multiple(),

            Tables\Filters\TernaryFilter::make('surge_multiplier')
                ->label('Есть surge (>1.0)')
                ->queries(
                    true: fn (Builder $query) => $query->where('surge_multiplier', '>', 1.0),
                    false: fn (Builder $query) => $query->where('surge_multiplier', '=', 1.0),
                ),

            Tables\Filters\Filter::make('high_rating')
                ->label('Высокая оценка (≥4.5)')
                ->query(fn (Builder $query) => $query->where('passenger_rating', '>=', 4.5)),

            Tables\Filters\Filter::make('completed_rides')
                ->label('Завершённые')
                ->query(fn (Builder $query) => $query->where('status', 'completed')),

            Tables\Filters\TrashedFilter::make(),
        ])
        ->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),

                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->label('Завершить')
                    ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                    ->action(function ($record) {
                        $record->update(['status' => 'completed', 'completed_at' => now()]);
                        Log::channel('audit')->info('Taxi ride completed', [
                            'ride_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->successNotification(),

                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->label('Отменить')
                    ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                    ->action(function ($record) {
                        $record->update(['status' => 'cancelled']);
                        Log::channel('audit')->info('Taxi ride cancelled', [
                            'ride_id' => $record->id,
                            'user_id' => auth()->id(),
                            'correlation_id' => $record->correlation_id,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->successNotification(),
            ]),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            Log::channel('audit')->info('Taxi ride bulk deleted', [
                                'ride_id' => $record->id,
                                'user_id' => auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        });
                    }),

                Tables\Actions\BulkAction::make('complete_bulk')
                    ->label('Завершить (массово)')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            if (!in_array($record->status, ['completed', 'cancelled'])) {
                                $record->update(['status' => 'completed', 'completed_at' => now()]);
                                Log::channel('audit')->info('Taxi ride bulk completed', [
                                    'ride_id' => $record->id,
                                    'user_id' => auth()->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            }
                        });
                    })
                    ->deselectRecordsAfterCompletion()
                    ->successNotification(),
            ]),
        ])
        ->defaultSort('requested_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\ListTaxiRides::route('/'),
            'create' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\CreateTaxiRide::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\ViewTaxiRide::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\EditTaxiRide::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with(['driver', 'vehicle']);
    }
}
