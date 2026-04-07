<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Travel;

use Filament\Resources\Resource;

final class TravelResource extends Resource
{

    protected static ?string $model = TravelTour::class;
        protected static ?string $navigationIcon = 'heroicon-o-map';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название тура')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('tour_code')->label('Код тура')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('type')->label('Тип тура')->options([
                            'city' => 'Городской тур',
                            'beach' => 'Пляжный отдых',
                            'adventure' => 'Приключения',
                            'cruise' => 'Круиз',
                            'hiking' => 'Пешеходный',
                            'culture' => 'Культурный',
                            'business' => 'Деловой',
                            'wellness' => 'Оздоровление'
                        ])->required()->columnSpan(1),
                        TextInput::make('agency_name')->label('Туроператор')->maxLength(100)->columnSpan(1),
                    ]),

                Section::make('Маршрут и направление')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('destinations')->label('Страны/города')->columnSpan(2),
                        TextInput::make('departure_city')->label('Город вылета')->columnSpan(1),
                        TextInput::make('country')->label('Основная страна')->maxLength(100)->columnSpan(1),
                        TextInput::make('region')->label('Регион')->maxLength(100)->columnSpan(1),
                        TextInput::make('duration_days')->label('Продолжительность (дней)')->numeric()->required()->columnSpan(1),
                    ]),

                Section::make('Описание и программа')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        RichEditor::make('itinerary')->label('Программа тура')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Даты и расписание')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('departure_date')->label('Дата вылета')->required()->columnSpan(1),
                        DatePicker::make('return_date')->label('Дата возврата')->required()->columnSpan(1),
                        TextInput::make('departure_time')->label('Время вылета')->columnSpan(1),
                        Toggle::make('is_recurring')->label('Еженедельный тур')->columnSpan(1),
                        TextInput::make('tour_slots')->label('Доступных мест')->numeric()->columnSpan(1),
                        TextInput::make('booked_slots')->label('Забронировано')->numeric()->columnSpan(1),
                    ]),

                Section::make('Размещение')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('hotel_name')->label('Название отеля')->columnSpan(1),
                        Select::make('hotel_star_rating')->label('Звёздность отеля')->options([
                            3 => '3 звезды',
                            4 => '4 звезды',
                            5 => '5 звёзд'
                        ])->columnSpan(1),
                        Select::make('room_type')->label('Тип номера')->options([
                            'single' => 'Одноместный',
                            'double' => 'Двухместный',
                            'suite' => 'Люкс',
                            'family' => 'Семейный'
                        ])->columnSpan(1),
                        Toggle::make('has_breakfast')->label('Включён завтрак')->columnSpan(1),
                        Toggle::make('has_all_inclusive')->label('All Inclusive')->columnSpan(1),
                        Toggle::make('transfer_included')->label('Трансфер включён')->columnSpan(1),
                    ]),

                Section::make('Транспорт')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('transport_type')->label('Тип транспорта')->options([
                            'flight' => 'Самолёт',
                            'train' => 'Поезд',
                            'car' => 'Автомобиль',
                            'bus' => 'Автобус',
                            'ship' => 'Корабль',
                            'mixed' => 'Смешанный'
                        ])->columnSpan(1),
                        TextInput::make('flight_number')->label('Номер рейса')->columnSpan(1),
                        TextInput::make('airline')->label('Авиакомпания')->columnSpan(1),
                        Toggle::make('luggage_included')->label('Багаж включён')->columnSpan(1),
                    ]),

                Section::make('Стоимость и оплата')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_per_person')->label('Цена за человека (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('price_for_child')->label('Цена для ребёнка (₽)')->numeric()->columnSpan(1),
                        TextInput::make('single_room_surcharge')->label('Доплата за одноместный (₽)')->numeric()->columnSpan(1),
                        TextInput::make('deposit_percent')->label('Предоплата (%)')->numeric()->columnSpan(1),
                        TextInput::make('cancellation_deadline')->label('Срок отмены (дней до вылета)')->numeric()->columnSpan(1),
                        Toggle::make('refundable')->label('Возвратный тур')->columnSpan(1),
                    ]),

                Section::make('Включение и условия')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('visa_support')->label('Помощь с визой')->columnSpan(1),
                        Toggle::make('travel_insurance')->label('Страховка туриста')->columnSpan(1),
                        Toggle::make('guide_included')->label('Гид включён')->columnSpan(1),
                        Toggle::make('meals_included')->label('Питание включено')->columnSpan(1),
                        Toggle::make('activities_included')->label('Экскурсии включены')->columnSpan(1),
                        Toggle::make('covid_safe')->label('COVID-safe программа')->columnSpan(1),
                    ]),

                Section::make('Аттракции и достопримечательности')
                    ->collapsed()
                    ->schema([
                        Repeater::make('attractions')->label('Включённые аттракции')
                            ->schema([
                                TextInput::make('name')->label('Название')->required(),
                                TextInput::make('category')->label('Категория')->required(),
                                Textarea::make('description')->label('Описание')->maxLength(500)->rows(2),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Оценки и отзывы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('avg_rating')->label('Средний рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('travel-main'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('travel-gallery')->columnSpan('full'),
                        FileUpload::make('map_image')->label('Карта маршрута')->image()->directory('travel-maps'),
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
                ImageColumn::make('main_image')->label('Фото')->size(50),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(35),
                TextColumn::make('destinations')->label('Направление')->limit(30),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('departure_date')->label('Вылет')->date('d M Y')->sortable(),
                TextColumn::make('duration_days')->label('Дней')->numeric(),
                TextColumn::make('price_per_person')->label('Цена (₽)')->numeric()->badge()->color('success'),
                TextColumn::make('avg_rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning'),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('tour_code')->label('Код')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'city' => 'Городской',
                    'beach' => 'Пляжный',
                    'adventure' => 'Приключения',
                    'cruise' => 'Круиз',
                ]),
                Filter::make('has_all_inclusive')->query(fn (Builder $q) => $q->where('has_all_inclusive', true))->label('All Inclusive'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('departure_date', 'asc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListTravel::route('/'),
                'create' => Pages\CreateTravel::route('/create'),
                'edit' => Pages\EditTravel::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
