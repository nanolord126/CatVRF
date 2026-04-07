<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\Filter, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class EventResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = Event::class;
        protected static ?string $navigationIcon = 'heroicon-o-ticket';
        protected static ?string $navigationGroup = 'Events & Entertainment';
        protected static ?string $label = 'Мероприятия';
        protected static ?string $pluralLabel = 'Мероприятия';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-calendar')
                    ->description('Описание мероприятия')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->copyable()
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->copyable()
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('poster_image')
                            ->label('Постер')
                            ->image()
                            ->directory('events')
                            ->columnSpan(1),

                        FileUpload::make('gallery_photos')
                            ->label('Галерея фото')
                            ->image()
                            ->multiple()
                            ->directory('events')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Дата и время')
                    ->icon('heroicon-m-clock')
                    ->description('Расписание мероприятия')
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Дата и время начала')
                            ->required()
                            ->native(false)
                            ->columnSpan(2),

                        DateTimePicker::make('end_at')
                            ->label('Дата и время окончания')
                            ->required()
                            ->native(false)
                            ->columnSpan(2),

                        TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        Select::make('event_type')
                            ->label('Тип мероприятия')
                            ->options([
                                'concert' => '🎵 Концерт',
                                'theater' => '🎭 Театр',
                                'cinema' => '🎬 Кино',
                                'sports' => '⚽ Спорт',
                                'exhibition' => '🖼️ Выставка',
                                'conference' => '💼 Конференция',
                                'festival' => '🎉 Фестиваль',
                                'comedy' => '😂 Комедия',
                                'other' => '📌 Другое',
                            ])
                            ->required()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Место проведения')
                    ->icon('heroicon-m-map-pin')
                    ->description('Адрес и локация')
                    ->schema([
                        TextInput::make('venue_name')
                            ->label('Название площадки')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('city')
                            ->label('Город')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('postal_code')
                            ->label('Почтовый индекс')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Билеты и категории')
                    ->icon('heroicon-m-ticket')
                    ->description('Типы и цены билетов')
                    ->schema([
                        Repeater::make('ticketTypes')
                            ->label('Категории билетов')
                            ->relationship()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Название категории')
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('price')
                                    ->label('Цена (₽)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('₽')
                                    ->columnSpan(1),

                                TextInput::make('available_count')
                                    ->label('Доступно билетов')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('row_start')
                                    ->label('Ряд начиная с')
                                    ->numeric()
                                    ->columnSpan(1),

                                TextInput::make('row_end')
                                    ->label('Ряд по')
                                    ->numeric()
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

                Section::make('Комиссии и условия')
                    ->icon('heroicon-m-banknote')
                    ->description('Стоимость и правила')
                    ->schema([
                        TextInput::make('commission_type')
                            ->label('Тип комиссии')
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('commission_value')
                            ->label('Комиссия платформы')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('refund_percent_before_event')
                            ->label('Возврат за 48 ч до (%)(%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        TextInput::make('min_per_transaction')
                            ->label('Минимум за транзакцию')
                            ->numeric()
                            ->suffix('₽')
                            ->columnSpan(1),

                        TextInput::make('service_fee')
                            ->label('Комиссия за сервис')
                            ->numeric()
                            ->suffix('₽')
                            ->columnSpan(2),

                        Toggle::make('guarantee_letter_required')
                            ->label('Требуется гарантийное письмо')
                            ->columnSpan(2),
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

                        Toggle::make('is_sold_out')
                            ->label('Распродано')
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

                        TagsInput::make('categories')
                            ->label('Категории')
                            ->columnSpan('full'),
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
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->limit(50),

                TextColumn::make('venue_name')
                    ->label('Площадка')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('event_type')
                    ->label('Тип')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'theater' => 'Театр',
                        'cinema' => 'Кино',
                        'sports' => 'Спорт',
                        'exhibition' => 'Выставка',
                        'conference' => 'Конференция',
                        'festival' => 'Фестиваль',
                        'comedy' => 'Комедия',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'theater' => 'blue',
                        'sports' => 'red',
                        'festival' => 'pink',
                        default => 'gray',
                    }),

                TextColumn::make('start_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Длительность')
                    ->formatStateUsing(fn ($state) => ($state / 60) . ' ч')
                    ->alignment('center'),

                TextColumn::make('ticketTypes_aggregate.min_price')
                    ->label('От (₽)')
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

                BooleanColumn::make('is_sold_out')
                    ->label('🏁 Распродано')
                    ->toggleable(),

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
                SelectFilter::make('event_type')
                    ->label('Тип мероприятия')
                    ->options([
                        'concert' => 'Концерт',
                        'theater' => 'Театр',
                        'cinema' => 'Кино',
                        'sports' => 'Спорт',
                        'exhibition' => 'Выставка',
                        'conference' => 'Конференция',
                        'festival' => 'Фестиваль',
                        'comedy' => 'Комедия',
                    ])
                    ->multiple(),

                SelectFilter::make('city')
                    ->label('Город')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_verified')
                    ->label('Проверен'),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_sold_out')
                    ->label('Распродано'),

                TernaryFilter::make('is_active')
                    ->label('Активен'),

                Filter::make('upcoming')
                    ->label('Предстоящие')
                    ->query(fn (Builder $query) => $query->where('start_at', '>', now())),

                Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

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
                            $this->logger->info('Event verified', [
                                'event_id' => $record->id,
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
                            $this->logger->info('Event featured', [
                                'event_id' => $record->id,
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
                                $this->logger->info('Event bulk deleted', [
                                    'event_id' => $record->id,
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
                                $this->logger->info('Event activated', [
                                    'event_id' => $record->id,
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
                                $this->logger->info('Event bulk verified', [
                                    'event_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),
                ]),
            ])
            ->defaultSort('start_at', 'asc');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Tickets\EventResource\Pages\ListEvents::route('/'),
                'create' => \App\Filament\Tenant\Resources\Tickets\EventResource\Pages\CreateEvent::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Tickets\EventResource\Pages\ViewEvent::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Tickets\EventResource\Pages\EditEvent::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}
