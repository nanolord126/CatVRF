<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ShortTermRentals;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
final class ApartmentResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static ?string $model = Apartment::class;
        protected static ?string $navigationIcon = 'heroicon-o-home';
        protected static ?string $navigationGroup = 'Short-Term Rentals';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Детали апартаментов и идентификаторы')
                    ->icon('heroicon-m-home')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Название')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Уютные апартаменты в центре'),

                            Forms\Components\TextInput::make('phone')
                                ->label('Телефон')
                                ->tel()
                                ->required()
                                ->placeholder('+7 (999) 000-0000')
                                ->copyable(),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->placeholder('contact@apartment.com')
                                ->copyable(),

                            Forms\Components\TextInput::make('contact_person')
                                ->label('Контактное лицо')
                                ->placeholder('Иван Иванов'),
                        ]),

                        Forms\Components\RichEditor::make('description')
                            ->label('Описание апартаментов')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Подробное описание, особенности, удобства'),

                        Forms\Components\FileUpload::make('main_photo')
                            ->label('Главное фото')
                            ->image()
                            ->directory('apartments/photos')
                            ->visibility('public')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('gallery_photos')
                            ->label('Галерея фото (несколько)')
                            ->image()
                            ->directory('apartments/gallery')
                            ->multiple()
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Местоположение')
                    ->description('Адрес, город и координаты')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Улица, дом, кв.'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('city')
                                ->label('Город')
                                ->required()
                                ->placeholder('Москва'),

                            Forms\Components\TextInput::make('postal_code')
                                ->label('Почтовый индекс')
                                ->placeholder('123456'),

                            Forms\Components\TextInput::make('latitude')
                                ->label('Широта')
                                ->numeric()
                                ->step(0.0001)
                                ->required(),

                            Forms\Components\TextInput::make('longitude')
                                ->label('Долгота')
                                ->numeric()
                                ->step(0.0001)
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Характеристики')
                    ->description('Размер, комнаты, вместимость')
                    ->icon('heroicon-m-squares-2x2')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('area_sqm')
                                ->label('Площадь (м²)')
                                ->numeric()
                                ->required()
                                ->minValue(10),

                            Forms\Components\TextInput::make('bedrooms')
                                ->label('Спальни')
                                ->numeric()
                                ->required()
                                ->minValue(0),

                            Forms\Components\TextInput::make('bathrooms')
                                ->label('Ванные')
                                ->numeric()
                                ->required()
                                ->minValue(1),

                            Forms\Components\TextInput::make('max_guests')
                                ->label('Макс. гостей')
                                ->numeric()
                                ->required()
                                ->minValue(1),

                            Forms\Components\Select::make('apartment_type')
                                ->label('Тип апартаментов')
                                ->options([
                                    'studio' => 'Студия',
                                    '1room' => '1-комнатная',
                                    '2room' => '2-комнатная',
                                    '3room' => '3-комнатная',
                                    '4room' => '4-комнатная',
                                    'house' => 'Дом',
                                    'villa' => 'Вилла',
                                    'loft' => 'Лофт',
                                ])
                                ->default('1room')
                                ->required(),

                            Forms\Components\Select::make('floor')
                                ->label('Этаж')
                                ->options(array_combine(range(0, 30), array_map(fn ($n) => $n === 0 ? '1 (цокольный)' : (string)$n, range(1, 30))))
                                ->placeholder('Выберите этаж')
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Цены и условия бронирования')
                    ->description('Тарифы, сборы и минимум ночей')
                    ->icon('heroicon-m-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('price_per_night')
                                ->label('Цена за ночь (₽)')
                                ->numeric()
                                ->step(100)
                                ->prefix('₽')
                                ->required()
                                ->minValue(0),

                            Forms\Components\TextInput::make('cleaning_fee')
                                ->label('Плата за уборку (₽)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('security_deposit')
                                ->label('Залог (₽)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('min_stay_days')
                                ->label('Минимум ночей')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('max_stay_days')
                                ->label('Максимум ночей (0 = без лимита)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ]),
                    ]),

                Forms\Components\Section::make('Удобства')
                    ->description('Доступные услуги и удобства')
                    ->icon('heroicon-m-wrench')
                    ->schema([
                        Forms\Components\TagsInput::make('amenities_list')
                            ->label('Удобства (через запятую)')
                            ->placeholder('WiFi, Кондиционер, Телевизор, Посудомойка')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Правила и политика')
                    ->description('Правила проживания, время заезда/выезда')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('check_in_time')
                                ->label('Время заезда')
                                ->options([
                                    '12:00' => '12:00',
                                    '13:00' => '13:00',
                                    '14:00' => '14:00',
                                    '15:00' => '15:00',
                                    '16:00' => '16:00',
                                    '17:00' => '17:00',
                                ])
                                ->default('15:00')
                                ->required(),

                            Forms\Components\Select::make('check_out_time')
                                ->label('Время выезда')
                                ->options([
                                    '09:00' => '09:00',
                                    '10:00' => '10:00',
                                    '11:00' => '11:00',
                                    '12:00' => '12:00',
                                    '13:00' => '13:00',
                                ])
                                ->default('11:00')
                                ->required(),

                            Forms\Components\Toggle::make('pets_allowed')
                                ->label('Животные разрешены')
                                ->default(false),

                            Forms\Components\Toggle::make('smoking_allowed')
                                ->label('Курение разрешено')
                                ->default(false),

                            Forms\Components\Toggle::make('parties_allowed')
                                ->label('Вечеринки разрешены')
                                ->default(false),

                            Forms\Components\Toggle::make('events_allowed')
                                ->label('Мероприятия разрешены')
                                ->default(false),
                        ]),

                        Forms\Components\RichEditor::make('house_rules')
                            ->label('Правила дома')
                            ->columnSpanFull()
                            ->placeholder('Список правил для гостей'),

                        Forms\Components\RichEditor::make('cancellation_policy')
                            ->label('Политика отмены')
                            ->columnSpanFull()
                            ->placeholder('Условия возврата денежных средств'),
                    ]),

                Forms\Components\Section::make('Финансовые параметры')
                    ->description('Комиссия и условия выплат')
                    ->icon('heroicon-m-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('commission_type')
                                ->label('Тип комиссии')
                                ->options([
                                    'percent' => 'Процент',
                                    'fixed' => 'Фиксированная сумма',
                                ])
                                ->default('percent')
                                ->required(),

                            Forms\Components\TextInput::make('commission_percent')
                                ->label('Размер комиссии (%)')
                                ->numeric()
                                ->step(0.1)
                                ->default(14)
                                ->required()
                                ->minValue(0),

                            Forms\Components\Select::make('payout_schedule')
                                ->label('График выплат')
                                ->options([
                                    'weekly' => 'Еженедельно',
                                    'biweekly' => 'Раз в две недели',
                                    'monthly' => 'Ежемесячно',
                                    'after_stay' => 'После выселения гостя',
                                ])
                                ->default('weekly')
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Управление и статус')
                    ->description('Активность, проверка, рейтинг')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Toggle::make('is_available')
                                ->label('Доступна для бронирования')
                                ->default(true)
                                ->required(),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Проверена администрацией')
                                ->default(false),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Рекомендуемая')
                                ->default(false),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг')
                                ->numeric()
                                ->step(0.1)
                                ->disabled()
                                ->default(4.5),

                            Forms\Components\TextInput::make('review_count')
                                ->label('Количество отзывов')
                                ->numeric()
                                ->disabled()
                                ->default(0),
                        ]),
                    ]),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => tenant('id')),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) Str::uuid()),
                Forms\Components\Hidden::make('business_group_id')
                    ->default(fn () => filament()->getTenant()?->active_business_group_id),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-home')
                    ->limit(50),

                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('apartment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        '1room' => '1-комн.',
                        '2room' => '2-комн.',
                        '3room' => '3-комн.',
                        '4room' => '4-комн.',
                        'house' => 'Дом',
                        'villa' => 'Вилла',
                        'loft' => 'Лофт',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        '1room' => 'green',
                        '2room' => 'blue',
                        '3room' => 'purple',
                        'house' => 'orange',
                        'villa' => 'amber',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Спал.')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('max_guests')
                    ->label('Макс. гостей')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('area_sqm')
                    ->label('м²')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('price_per_night')
                    ->label('Цена/ночь')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4 => 'info',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_available')
                    ->label('Доступна')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_verified')
                    ->label('✓ Проверена')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемая')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('apartment_type')
                    ->label('Тип')
                    ->options([
                        'studio' => 'Студия',
                        '1room' => '1-комнатная',
                        '2room' => '2-комнатная',
                        '3room' => '3-комнатная',
                        '4room' => '4-комнатная',
                        'house' => 'Дом',
                        'villa' => 'Вилла',
                        'loft' => 'Лофт',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('city')
                    ->label('Город')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Доступна для бронирования'),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Проверена'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемая'),

                Tables\Filters\Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                Tables\Filters\Filter::make('price_budget')
                    ->label('Бюджет (до 5000 ₽)')
                    ->query(fn (Builder $query) => $query->where('price_per_night', '<', 500000)),

                Tables\Filters\TrashedFilter::make()
                    ->label('Удалённые'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('verify')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => !$record->is_verified)
                        ->action(function ($record) {
                            $record->update(['is_verified' => true]);
                            $this->logger->info('Apartment verified', [
                                'apartment_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Tables\Actions\Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('В рекомендуемые')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            $this->logger->info('Apartment featured', [
                                'apartment_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $this->logger->info('Apartment bulk deleted', [
                                    'apartment_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        }),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_available' => true]);
                                $this->logger->info('Apartment activated', [
                                    'apartment_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_available' => false]);
                                $this->logger->info('Apartment deactivated', [
                                    'apartment_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    Tables\Actions\BulkAction::make('verify')
                        ->label('Подтвердить')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_verified' => true]);
                                $this->logger->info('Apartment bulk verified', [
                                    'apartment_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListApartments::route('/'),
                'create' => Pages\CreateApartment::route('/create'),
                'view' => Pages\ViewApartment::route('/{record}'),
                'edit' => Pages\EditApartment::route('/{record}/edit'),
            ];
        }

        public static function getRelations(): array
        {
            return [];
        }

        protected static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}
