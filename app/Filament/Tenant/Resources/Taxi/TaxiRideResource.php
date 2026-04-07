<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi;

use Filament\Resources\Resource;

final class TaxiRideResource extends Resource
{

    protected static ?string $model = TaxiRide::class;

        protected static ?string $navigationIcon = 'heroicon-o-truck';

        protected static ?string $navigationGroup = 'Taxi & Logistics';

        protected static ?string $label = 'Поездки такси';

        protected static ?string $pluralLabel = 'Все поездки';

        /**
         * Tenant Scoping (по канону: изоляция данных).
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id)
                ->with(['passenger', 'driver', 'fleet', 'vehicle']);
        }

        /**
         * Форма создания/редактирования (подробная валидация).
         */
        public function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->schema([
                            Forms\Components\Select::make('passenger_id')
                                ->relationship('passenger', 'name')
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('driver_id')
                                ->relationship('driver', 'full_name')
                                ->searchable()
                                ->nullable(),
                            Forms\Components\Select::make('fleet_id')
                                ->relationship('fleet', 'name')
                                ->searchable()
                                ->nullable(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Ожидание',
                                    'accepted' => 'Принято',
                                    'in_progress' => 'В пути',
                                    'completed' => 'Завершено',
                                    'cancelled' => 'Отменено',
                                ])
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Маршрут и цена')
                        ->schema([
                            Forms\Components\TextInput::make('pickup_address')
                                ->required(),
                            Forms\Components\TextInput::make('dropoff_address')
                                ->required(),
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->prefix('коп.')
                                ->required(),
                            Forms\Components\TextInput::make('surge_multiplier')
                                ->numeric()
                                ->step(0.1)
                                ->default(1.0),
                        ])->columns(2),

                    Forms\Components\Section::make('Техническая инфо')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->disabled(),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled(),
                            Forms\Components\KeyValue::make('metadata')
                                ->keyLabel('Параметр')
                                ->valueLabel('Значение'),
                            Forms\Components\TagsInput::make('tags'),
                        ])->collapsed(),
                ]);
        }

        /**
         * Таблица списка поездок (мощная фильтрация).
         */
        public function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('uuid')
                        ->label('ID')
                        ->limit(8)
                        ->copyable()
                        ->searchable(),

                    TextColumn::make('passenger.name')
                        ->label('Пассажир')
                        ->searchable(),

                    TextColumn::make('driver.full_name')
                        ->label('Водитель')
                        ->placeholder('Не назначен')
                        ->searchable(),

                    BadgeColumn::make('status')
                        ->label('Статус')
                        ->colors([
                            'warning' => 'pending',
                            'primary' => 'accepted',
                            'info' => 'in_progress',
                            'success' => 'completed',
                            'danger' => 'cancelled',
                        ]),

                    TextColumn::make('price')
                        ->label('Цена')
                        ->formatStateUsing(fn ($state) => round($state / 100, 2) . ' ₽')
                        ->sortable(),

                    TextColumn::make('pickup_address')
                        ->label('Откуда')
                        ->limit(20)
                        ->tooltip(fn ($record) => $record->pickup_address),

                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->options([
                            'pending' => 'Ожидание',
                            'in_progress' => 'В пути',
                            'completed' => 'Завершено',
                        ]),
                    SelectFilter::make('fleet_id')
                        ->relationship('fleet', 'name')
                        ->label('Автопарк'),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Taxi\TaxiRideResource\Pages\ListTaxiRides::route('/'),
                'create' => \App\Filament\Tenant\Resources\Taxi\TaxiRideResource\Pages\CreateTaxiRide::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Taxi\TaxiRideResource\Pages\EditTaxiRide::route('/{record}/edit'),
            ];
        }
}
