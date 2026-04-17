<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals;

use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use App\Domains\ShortTermRentals\Models\Property;
final class PropertyResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}



    protected static ?string $model = Property::class;

        protected static ?string $navigationIcon = 'heroicon-o-home';
        protected static ?string $navigationLabel = 'Квартиры (STR)';
        protected static ?string $navigationGroup = 'ShortTermRentals';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->description('Основные данные квартиры')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Название')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('description')
                                ->label('Описание')
                                ->required()
                                ->rows(4)
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('address')
                                ->label('Адрес')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('city')
                                        ->label('Город')
                                        ->required(),

                                    Forms\Components\TextInput::make('postal_code')
                                        ->label('Почтовый индекс')
                                        ->required(),
                                ]),
                        ]),

                    Forms\Components\Section::make('Характеристики')
                        ->description('Параметры и удобства')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('bedrooms')
                                        ->label('Спальни')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),

                                    Forms\Components\TextInput::make('bathrooms')
                                        ->label('Ванные')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1),

                                    Forms\Components\TextInput::make('area_m2')
                                        ->label('Площадь (м²)')
                                        ->numeric()
                                        ->required(),

                                    Forms\Components\TextInput::make('max_guests')
                                        ->label('Максимум гостей')
                                        ->numeric()
                                        ->required(),
                                ]),

                            Forms\Components\CheckboxList::make('amenities')
                                ->label('Удобства')
                                ->options([
                                    'wifi' => 'Wi-Fi',
                                    'parking' => 'Парковка',
                                    'washing_machine' => 'Стиральная машина',
                                    'dishwasher' => 'Посудомойка',
                                    'air_conditioning' => 'Кондиционер',
                                    'heating' => 'Отопление',
                                    'kitchen' => 'Кухня',
                                    'tv' => 'Телевизор',
                                    'gym' => 'Спортзал',
                                    'pool' => 'Бассейн',
                                ])
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Section::make('Ценообразование')
                        ->description('Стоимость и комиссии')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('price_per_night')
                                        ->label('Цена за ночь (руб)')
                                        ->numeric()
                                        ->required()
                                        ->minValue(100),

                                    Forms\Components\TextInput::make('cleaning_fee')
                                        ->label('Комиссия уборки (руб)')
                                        ->numeric()
                                        ->default(0),

                                    Forms\Components\TextInput::make('service_fee_percent')
                                        ->label('Комиссия сервиса (%)')
                                        ->numeric()
                                        ->default(14)
                                        ->minValue(0)
                                        ->maxValue(50),

                                    Forms\Components\TextInput::make('platform_fee_percent')
                                        ->label('Комиссия платформы (%)')
                                        ->numeric()
                                        ->default(14)
                                        ->minValue(0)
                                        ->maxValue(50),
                                ]),

                            Forms\Components\TextInput::make('deposit_percent')
                                ->label('Размер депозита (%)')
                                ->numeric()
                                ->default(25)
                                ->minValue(0)
                                ->maxValue(100),
                        ]),

                    Forms\Components\Section::make('Статус')
                        ->description('Активность и видимость')
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Активна'),

                            Forms\Components\Toggle::make('is_b2c_available')
                                ->label('Доступна для физлиц (B2C)'),

                            Forms\Components\Toggle::make('is_b2b_available')
                                ->label('Доступна для бизнеса (B2B)'),

                            Forms\Components\Toggle::make('requires_id_verification')
                                ->label('Требуется проверка ID')
                                ->default(true),

                            Forms\Components\Toggle::make('instant_booking')
                                ->label('Мгновенное бронирование'),
                        ]),
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

                    Tables\Columns\TextColumn::make('address')
                        ->label('Адрес')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('bedrooms')
                        ->label('Спал.')
                        ->numeric(),

                    Tables\Columns\TextColumn::make('max_guests')
                        ->label('Гостей')
                        ->numeric(),

                    Tables\Columns\TextColumn::make('price_per_night')
                        ->label('Цена/ночь')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽')
                        ->sortable(),

                    Tables\Columns\BadgeColumn::make('is_active')
                        ->label('Статус')
                        ->formatStateUsing(fn (bool $state) => $state ? 'Активна' : 'Неактивна')
                        ->color(fn (bool $state) => $state ? 'success' : 'danger'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создана')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('city')
                        ->label('Город')
                        ->options(Property::pluck('city', 'city')->toArray()),

                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Активна'),

                    Tables\Filters\TernaryFilter::make('is_b2c_available')
                        ->label('B2C доступна'),

                    Tables\Filters\TernaryFilter::make('is_b2b_available')
                        ->label('B2B доступна'),

                    Tables\Filters\Filter::make('price_range')
                        ->form([
                            Forms\Components\TextInput::make('min_price')
                                ->numeric()
                                ->label('Минимальная цена'),
                            Forms\Components\TextInput::make('max_price')
                                ->numeric()
                                ->label('Максимальная цена'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when($data['min_price'] ?? null, fn ($q) => $q->where('price_per_night', '>=', $data['min_price'] * 100))
                                ->when($data['max_price'] ?? null, fn ($q) => $q->where('price_per_night', '<=', $data['max_price'] * 100));
                        }),
                ])
                ->actions([
                    Tables\Actions\EditAction::make()
                        ->label('Редактировать'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Property $record) => $record->is_active ? 'Отключить' : 'Включить')
                        ->action(function (Property $record) {
                            $record->update(['is_active' => !$record->is_active]);

                            $this->logger->info('Property status toggled', [
                                'property_id' => $record->id,
                                'new_status' => $record->is_active,
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->color(fn (Property $record) => $record->is_active ? 'danger' : 'success'),

                    Tables\Actions\DeleteAction::make()
                        ->label('Удалить')
                        ->after(function (Property $record) {
                            $this->logger->warning('Property deleted', [
                                'property_id' => $record->id,
                                'correlation_id' => $record->correlation_id,
                            ]);
                        }),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ])
                ->getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id)
                ->latest('created_at');
        }

        public static function getPages(): array
        {
            return [
                'index' => \Filament\Resources\Pages\ListRecords::route('/'),
                'create' => \Filament\Resources\Pages\CreateRecord::route('/create'),
                'edit' => \Filament\Resources\Pages\EditRecord::route('/{record}/edit'),
            ];
        }
    }

    final class BookingResource extends Resource
    {
        protected static ?string $model = PropertyBooking::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar';
        protected static ?string $navigationLabel = 'Бронирования (STR)';
        protected static ?string $navigationGroup = 'ShortTermRentals';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Информация о бронировании')
                        ->description('Детали бронирования (только для просмотра)')
                        ->schema([
                            Forms\Components\TextInput::make('id')
                                ->label('ID бронирования')
                                ->disabled(),

                            Forms\Components\TextInput::make('property.name')
                                ->label('Квартира')
                                ->disabled(),

                            Forms\Components\TextInput::make('user.name')
                                ->label('Гость')
                                ->disabled(),

                            Forms\Components\DateTimeInput::make('check_in_date')
                                ->label('Заезд')
                                ->disabled(),

                            Forms\Components\DateTimeInput::make('check_out_date')
                                ->label('Выезд')
                                ->disabled(),

                            Forms\Components\TextInput::make('guest_count')
                                ->label('Гостей')
                                ->numeric()
                                ->disabled(),
                        ]),

                    Forms\Components\Section::make('Финансы')
                        ->description('Стоимость и платежи')
                        ->schema([
                            Forms\Components\TextInput::make('total_price')
                                ->label('Итого (руб)')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('deposit_amount')
                                ->label('Депозит (руб)')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('platform_fee')
                                ->label('Комиссия платформы (руб)')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('payment_status')
                                ->label('Статус платежа')
                                ->disabled(),
                        ]),

                    Forms\Components\Section::make('Статус бронирования')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'pending_verification' => 'Ожидание проверки',
                                    'confirmed' => 'Подтверждено',
                                    'checked_in' => 'Заезд завершён',
                                    'checked_out' => 'Выезд завершён',
                                    'cancelled' => 'Отменено',
                                ])
                                ->disabled(),

                            Forms\Components\TextInput::make('cancellation_reason')
                                ->label('Причина отмены')
                                ->maxLength(500)
                                ->disabled(),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('property.name')
                        ->label('Квартира')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('user.name')
                        ->label('Гость')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('check_in_date')
                        ->label('Заезд')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('check_out_date')
                        ->label('Выезд')
                        ->dateTime('d.m.Y H:i'),

                    Tables\Columns\TextColumn::make('total_price')
                        ->label('Сумма')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽'),

                    Tables\Columns\BadgeColumn::make('status')
                        ->label('Статус')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'confirmed' => 'Подтверждено',
                            'checked_in' => 'Заезд',
                            'checked_out' => 'Выезд',
                            'cancelled' => 'Отменено',
                            default => $state,
                        })
                        ->color(fn (string $state) => match ($state) {
                            'confirmed' => 'success',
                            'checked_in' => 'info',
                            'checked_out' => 'gray',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        }),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending_verification' => 'Ожидание проверки',
                            'confirmed' => 'Подтверждено',
                            'checked_in' => 'Заезд',
                            'checked_out' => 'Выезд',
                            'cancelled' => 'Отменено',
                        ]),

                    Tables\Filters\Filter::make('date_range')
                        ->form([
                            Forms\Components\DatePicker::make('from')
                                ->label('От'),
                            Forms\Components\DatePicker::make('to')
                                ->label('До'),
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when($data['from'] ?? null, fn ($q) => $q->where('check_in_date', '>=', $data['from']))
                                ->when($data['to'] ?? null, fn ($q) => $q->where('check_out_date', '<=', $data['to']));
                        }),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make()
                        ->label('Просмотр'),

                    Tables\Actions\Action::make('approve')
                        ->label('Подтвердить')
                        ->visible(fn (PropertyBooking $record) => $record->status === 'pending_verification')
                        ->action(function (PropertyBooking $record) {
                            $record->update(['status' => 'confirmed']);

                            $this->logger->info('Booking approved', [
                                'booking_id' => $record->id,
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->color('success'),

                    Tables\Actions\Action::make('cancel')
                        ->label('Отменить')
                        ->visible(fn (PropertyBooking $record) => !in_array($record->status, ['cancelled', 'checked_out']))
                        ->action(function (PropertyBooking $record) {
                            $record->update(['status' => 'cancelled']);

                            $this->logger->warning('Booking cancelled', [
                                'booking_id' => $record->id,
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->color('danger'),
                ])
                ->getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id)
                ->latest('created_at');
        }

        public static function getPages(): array
        {
            return [
                'index' => \Filament\Resources\Pages\ListRecords::route('/'),
                'view' => \Filament\Resources\Pages\ViewRecord::route('/{record}'),
            ];
        }
}
