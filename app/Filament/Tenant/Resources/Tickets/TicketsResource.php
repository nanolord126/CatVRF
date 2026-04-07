<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Tickets;

use Filament\Resources\Resource;

final class TicketsResource extends Resource
{

    protected static ?string $model = Event::class;
        protected static ?string $navigationIcon = 'heroicon-o-ticket';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название события')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('event_code')->label('Код события')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('category')->label('Категория')->options([
                            'concert' => 'Концерт',
                            'theater' => 'Театр',
                            'cinema' => 'Кинотеатр',
                            'sports' => 'Спорт',
                            'comedy' => 'Комедия',
                            'festival' => 'Фестиваль',
                            'conference' => 'Конференция',
                            'exhibition' => 'Выставка',
                            'show' => 'Шоу'
                        ])->required()->columnSpan(1),
                        TagsInput::make('genres')->label('Жанры')->columnSpan(1),
                    ]),

                Section::make('Место проведения')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('venue_name')->label('Название площадки')->required()->columnSpan(2),
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500)->columnSpan(2),
                        TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                        TextInput::make('postal_code')->label('Почтовый индекс')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                        TextInput::make('venue_capacity')->label('Вместимость')->numeric()->columnSpan(1),
                        TextInput::make('parking_spaces')->label('Мест для парковки')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание события')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('performers')->label('Артисты/участники')->columnSpan('full'),
                    ]),

                Section::make('Дата и время')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('event_date')->label('Дата события')->required()->columnSpan(1),
                        TextInput::make('event_start_time')->label('Время начала')->required()->columnSpan(1),
                        TextInput::make('event_end_time')->label('Время окончания')->columnSpan(1),
                        TextInput::make('door_opening_time')->label('Начало входа')->columnSpan(1),
                        DatePicker::make('sales_end_date')->label('Продажи заканчиваются')->required()->columnSpan(1),
                        Toggle::make('is_recurring')->label('Повторяющееся событие')->columnSpan(1),
                    ]),

                Section::make('Типы билетов и цены')
                    ->collapsed()
                    ->schema([
                        Repeater::make('ticket_types')->label('Категории билетов')
                            ->schema([
                                TextInput::make('category')->label('Категория')->required(),
                                TextInput::make('price')->label('Цена (₽)')->numeric()->required(),
                                TextInput::make('available_count')->label('Доступно')->numeric()->required(),
                                TextInput::make('row_start')->label('Начальный ряд')->numeric(),
                                TextInput::make('row_end')->label('Конечный ряд')->numeric(),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Информация о билетах')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_tickets')->label('Всего билетов')->numeric()->columnSpan(1),
                        TextInput::make('sold_tickets')->label('Продано')->numeric()->columnSpan(1),
                        TextInput::make('available_tickets')->label('Доступно')->numeric()->columnSpan(1),
                        TextInput::make('early_bird_discount')->label('Ранний бёрд (%)')->numeric()->columnSpan(1),
                        DatePicker::make('early_bird_end_date')->label('Ранний бёрд до')->columnSpan(1),
                        Toggle::make('has_vip_seating')->label('VIP места')->columnSpan(1),
                    ]),

                Section::make('Комиссия и условия')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('platform_commission_percent')->label('Комиссия платформы (%)')->numeric()->columnSpan(1),
                        TextInput::make('min_commission_rubles')->label('Минимальная комиссия (₽)')->numeric()->columnSpan(1),
                        TextInput::make('refund_deadline_days')->label('Возврат за дней до события')->numeric()->columnSpan(1),
                        Toggle::make('refund_allowed')->label('Возврат разрешён')->columnSpan(1),
                        Toggle::make('partial_refund_allowed')->label('Частичный возврат')->columnSpan(1),
                        TextInput::make('refund_fee_percent')->label('Комиссия при возврате (%)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Правила доступа')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('age_restriction')->label('Возрастное ограничение')->options([
                            '0' => '0+',
                            '6' => '6+',
                            '12' => '12+',
                            '16' => '16+',
                            '18' => '18+'
                        ])->columnSpan(1),
                        Toggle::make('cashless_only')->label('Только безналичный расчёт')->columnSpan(1),
                        Toggle::make('bag_check')->label('Проверка сумок')->columnSpan(1),
                        Toggle::make('phone_free_zone')->label('Зона без телефонов')->columnSpan(1),
                        Textarea::make('house_rules')->label('Правила посещения')->maxLength(1000)->rows(3)->columnSpan(2),
                    ]),

                Section::make('Интеграции и доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_print_option')->label('Печать билета дома')->columnSpan(1),
                        Toggle::make('has_mobile_ticket')->label('Мобильный билет')->columnSpan(1),
                        Toggle::make('has_email_delivery')->label('Доставка по Email')->columnSpan(1),
                        Toggle::make('has_postal_delivery')->label('Почтовая доставка')->columnSpan(1),
                        TextInput::make('postal_delivery_price')->label('Цена доставки (₽)')->numeric()->columnSpan(1),
                        Toggle::make('guarantee_letter')->label('Гарантийное письмо')->columnSpan(1),
                    ]),

                Section::make('Оценки и популярность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('avg_rating')->label('Средний рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                        TextInput::make('view_count')->label('Просмотры')->numeric()->columnSpan(1),
                        TextInput::make('wishlist_count')->label('В вишлистах')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('poster')->label('Постер')->image()->directory('tickets-poster'),
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('tickets-main'),
                        FileUpload::make('seating_map')->label('Схема зала')->image()->directory('tickets-seating')->columnSpan('full'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('tickets-gallery')->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('meta_keywords')->label('Meta Keywords')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активно')->default(true),
                        Toggle::make('is_featured')->label('Избранное')->default(false),
                        Toggle::make('verified')->label('Проверено')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('poster')->label('Постер')->size(40),
                TextColumn::make('name')->label('Событие')->searchable()->sortable()->weight('bold')->limit(35),
                TextColumn::make('category')->label('Категория')->badge()->color('info'),
                TextColumn::make('event_date')->label('Дата')->date('d M Y')->sortable(),
                TextColumn::make('venue_name')->label('Площадка')->searchable()->limit(30),
                TextColumn::make('available_tickets')->label('Билетов')->numeric()->badge()->color('success'),
                TextColumn::make('avg_rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning'),
                BadgeColumn::make('has_vip_seating')->label('VIP')->colors(['warning' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('event_code')->label('Код')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('category')->options([
                    'concert' => 'Концерт',
                    'theater' => 'Театр',
                    'sports' => 'Спорт',
                    'comedy' => 'Комедия',
                    'festival' => 'Фестиваль',
                ]),
                Filter::make('has_vip')->query(fn (Builder $q) => $q->where('has_vip_seating', true))->label('С VIP'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('event_date', 'asc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListTickets::route('/'),
                'create' => Pages\CreateTickets::route('/create'),
                'edit' => Pages\EditTickets::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
