<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Columns\ImageColumn, Filters\Filter, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class TourResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = Tour::class;
        protected static ?string $navigationIcon = 'heroicon-o-map';
        protected static ?string $navigationGroup = 'Travel & Accommodation';
        protected static ?string $label = 'Туры';
        protected static ?string $pluralLabel = 'Туры';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-globe-alt')
                    ->description('Описание тура')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('title')
                            ->label('Название тура')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('destination_country')
                            ->label('Страна назначения')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('destination_city')
                            ->label('Город')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('phone')
                            ->label('Контактный телефон')
                            ->tel()
                            ->copyable()
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->copyable()
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание маршрута')
                            ->columnSpan('full'),

                        FileUpload::make('main_photo')
                            ->label('Главное фото')
                            ->image()
                            ->directory('tours')
                            ->columnSpan(1),

                        FileUpload::make('gallery_photos')
                            ->label('Галерея фото')
                            ->image()
                            ->multiple()
                            ->directory('tours')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Даты и длительность')
                    ->icon('heroicon-m-calendar')
                    ->description('Расписание тура')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Дата отправления')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        DatePicker::make('end_date')
                            ->label('Дата возвращения')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        TextInput::make('duration_days')
                            ->label('Длительность (дней)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('duration_nights')
                            ->label('Ночей')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        Select::make('season')
                            ->label('Сезон')
                            ->options([
                                'spring' => '🌸 Весна (март-май)',
                                'summer' => '☀️ Лето (июнь-август)',
                                'autumn' => '🍂 Осень (сентябрь-ноябрь)',
                                'winter' => '❄️ Зима (декабрь-февраль)',
                            ])
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Тип и категория тура')
                    ->icon('heroicon-m-tag')
                    ->description('Классификация')
                    ->schema([
                        Select::make('tour_type')
                            ->label('Тип тура')
                            ->options([
                                'package' => '📦 Пакетный тур',
                                'individual' => '👤 Индивидуальный',
                                'group' => '👥 Групповой',
                                'cruise' => '🚢 Круиз',
                                'adventure' => '⛰️ Приключение',
                                'cultural' => '🏛️ Культурный',
                                'beach' => '🏖️ Пляжный',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('difficulty_level')
                            ->label('Уровень сложности')
                            ->options([
                                'easy' => 'Лёгкий',
                                'moderate' => 'Средний',
                                'difficult' => 'Сложный',
                                'extreme' => 'Экстрем',
                            ])
                            ->columnSpan(1),

                        TextInput::make('min_participants')
                            ->label('Минимум участников')
                            ->numeric()
                            ->minValue(1)
                            ->columnSpan(1),

                        TextInput::make('max_participants')
                            ->label('Максимум участников')
                            ->numeric()
                            ->minValue(1)
                            ->columnSpan(1),

                        TextInput::make('age_min')
                            ->label('Минимальный возраст')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1),

                        TagsInput::make('keywords')
                            ->label('Ключевые слова (пляж, горы, история)')
                            ->columnSpan('full'),
                    ])->columns(4),

                Section::make('Цены и условия')
                    ->icon('heroicon-m-banknote')
                    ->description('Стоимость и правила')
                    ->schema([
                        TextInput::make('price_per_person')
                            ->label('Цена за человека (₽)')
                            ->numeric()
                            ->required()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('discount_percent')
                            ->label('Скидка (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        TextInput::make('final_price')
                            ->label('Финальная цена')
                            ->numeric()
                            ->disabled()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('deposit_required')
                            ->label('Залог (% от цены)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        TextInput::make('cancellation_days')
                            ->label('Отмена за N дней бесплатно')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1),

                        TextInput::make('commission_percent')
                            ->label('Комиссия платформы (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        Textarea::make('includes')
                            ->label('Включено в тур')
                            ->rows(3)
                            ->columnSpan(2),

                        Textarea::make('excludes')
                            ->label('Не включено')
                            ->rows(3)
                            ->columnSpan(2),

                        RichEditor::make('cancellation_policy')
                            ->label('Политика отмены')
                            ->columnSpan('full'),
                    ])->columns(4),

                Section::make('Включенные услуги')
                    ->icon('heroicon-m-check-circle')
                    ->description('Гостиницы, питание, транспорт')
                    ->schema([
                        Toggle::make('includes_accommodation')
                            ->label('Проживание включено')
                            ->columnSpan(1),

                        Select::make('accommodation_class')
                            ->label('Класс отеля')
                            ->options([
                                'budget' => '⭐ Бюджет',
                                'standard' => '⭐⭐ Стандарт',
                                'comfort' => '⭐⭐⭐ Комфорт',
                                'premium' => '⭐⭐⭐⭐ Премиум',
                                'luxury' => '⭐⭐⭐⭐⭐ Люкс',
                            ])
                            ->columnSpan(1),

                        Toggle::make('includes_meals')
                            ->label('Питание включено')
                            ->columnSpan(1),

                        Select::make('meal_type')
                            ->label('Тип питания')
                            ->options([
                                'bb' => 'Завтрак',
                                'hb' => 'Завтрак + Ужин',
                                'ai' => 'Все включено',
                            ])
                            ->columnSpan(1),

                        Toggle::make('includes_transport')
                            ->label('Транспорт включен')
                            ->columnSpan(1),

                        Select::make('transport_type')
                            ->label('Вид транспорта')
                            ->options([
                                'coach' => '🚌 Автобус',
                                'flight' => '✈️ Авиаперелёт',
                                'train' => '🚂 Поезд',
                                'mixed' => '🚗 Смешанный',
                            ])
                            ->columnSpan(1),

                        Toggle::make('includes_guide')
                            ->label('Гид включён')
                            ->columnSpan(1),

                        TextInput::make('guide_language')
                            ->label('Языки гида')
                            ->columnSpan(1),

                        Toggle::make('includes_insurance')
                            ->label('Страховка включена')
                            ->columnSpan(1),

                        TextInput::make('insurance_provider')
                            ->label('Страховая компания')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Статус и управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->description('Публикация и видимость')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_verified')
                            ->label('✓ Проверен')
                            ->columnSpan(1),

                        Toggle::make('is_featured')
                            ->label('⭐ Рекомендуемый')
                            ->columnSpan(1),

                        Toggle::make('is_popular')
                            ->label('🔥 Популярный')
                            ->columnSpan(1),

                        TextInput::make('rating')
                            ->label('Рейтинг (0-5)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('review_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn () => tenant('id')),

                        Hidden::make('correlation_id')
                            ->default(fn () => Str::uuid()),

                        Hidden::make('business_group_id')
                            ->default(fn () => filament()->getTenant()?->active_business_group_id),

                        TextInput::make('created_at')
                            ->label('Создан')
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('updated_at')
                            ->label('Обновлён')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('title')
                    ->label('Название тура')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-globe-alt')
                    ->limit(50),

                TextColumn::make('destination_country')
                    ->label('Страна')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('destination_city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('tour_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'individual' => 'Индивидуальный',
                        'group' => 'Групповой',
                        'cruise' => 'Круиз',
                        'adventure' => 'Приключение',
                        'cultural' => 'Культурный',
                        'beach' => 'Пляжный',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'adventure' => 'red',
                        'beach' => 'cyan',
                        'cultural' => 'purple',
                        default => 'gray',
                    }),

                TextColumn::make('duration_days')
                    ->label('Дней')
                    ->numeric()
                    ->alignment('center')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Отправление')
                    ->date('d.m')
                    ->sortable(),

                TextColumn::make('price_per_person')
                    ->label('Цена за чел.')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('final_price')
                    ->label('Финальная')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('rating')
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

                TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->numeric()
                    ->alignment('center'),

                BooleanColumn::make('is_verified')
                    ->label('✓ Проверен')
                    ->toggleable()
                    ->sortable(),

                BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемый')
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Активен')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tour_type')
                    ->label('Тип тура')
                    ->options([
                        'package' => 'Пакетный',
                        'individual' => 'Индивидуальный',
                        'group' => 'Групповой',
                        'cruise' => 'Круиз',
                        'adventure' => 'Приключение',
                        'cultural' => 'Культурный',
                        'beach' => 'Пляжный',
                    ])
                    ->multiple(),

                SelectFilter::make('destination_country')
                    ->label('Страна')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('season')
                    ->label('Сезон')
                    ->options([
                        'spring' => 'Весна',
                        'summer' => 'Лето',
                        'autumn' => 'Осень',
                        'winter' => 'Зима',
                    ])
                    ->multiple(),

                TernaryFilter::make('is_verified')
                    ->label('Проверен'),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_active')
                    ->label('Активен'),

                Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                Filter::make('premium_tours')
                    ->label('Премиум туры (>50k ₽)')
                    ->query(fn (Builder $query) => $query->where('price_per_person', '>', 5000000)),

                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),

                    Action::make('verify')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => !$record->is_verified)
                        ->action(function ($record) {
                            $record->update(['is_verified' => true]);
                            $this->logger->info('Tour verified', [
                                'tour_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('В рекомендуемые')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            $this->logger->info('Tour featured', [
                                'tour_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $this->logger->info('Tour bulk deleted', [
                                    'tour_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        }),

                    BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                                $this->logger->info('Tour activated', [
                                    'tour_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                                $this->logger->info('Tour deactivated', [
                                    'tour_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('verify')
                        ->label('Подтвердить')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_verified' => true]);
                                $this->logger->info('Tour bulk verified', [
                                    'tour_id' => $record->id,
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

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Travel\TourResource\Pages\ListTours::route('/'),
                'create' => \App\Filament\Tenant\Resources\Travel\TourResource\Pages\CreateTour::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Travel\TourResource\Pages\ViewTour::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Travel\TourResource\Pages\EditTour::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}
