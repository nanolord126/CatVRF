<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;

use Filament\Resources\Resource;

final class FoodResource extends Resource
{

    protected static ?string $model = Restaurant::class;
        protected static ?string $navigationIcon = 'heroicon-o-utensils';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('restaurant_code')->label('Код ресторана')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'restaurant' => 'Ресторан',
                            'cafe' => 'Кафе',
                            'diner' => 'Кофейня',
                            'fast_food' => 'Быстрое питание',
                            'pizzeria' => 'Пиццерия',
                            'sushi' => 'Суши',
                            'bakery' => 'Пекарня',
                            'catering' => 'Кейтеринг'
                        ])->required()->columnSpan(1),
                        TextInput::make('brand')->label('Бренд/Сеть')->maxLength(100)->columnSpan(1),
                    ]),

                Section::make('Местоположение и контакты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500)->columnSpan(2),
                        TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                        TextInput::make('postal_code')->label('Почтовый индекс')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                    ]),

                Section::make('Описание и тип кухни')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3)->columnSpan(2),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('cuisine_types')->label('Тип кухни')->columnSpan(2),
                    ]),

                Section::make('Режим работы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('opening_time')->label('Открытие')->columnSpan(1),
                        TextInput::make('closing_time')->label('Закрытие')->columnSpan(1),
                        Toggle::make('open_weekends')->label('Работает выходные')->columnSpan(1),
                        Toggle::make('open_holidays')->label('Работает праздники')->columnSpan(1),
                        Textarea::make('working_hours_description')->label('Расписание (описание)')->maxLength(500)->rows(2)->columnSpan(2),
                    ]),

                Section::make('Меню и специализация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_breakfast')->label('Завтраки')->columnSpan(1),
                        Toggle::make('has_lunch')->label('Обеды')->columnSpan(1),
                        Toggle::make('has_dinner')->label('Ужины')->columnSpan(1),
                        Toggle::make('has_desserts')->label('Десерты')->columnSpan(1),
                        Toggle::make('offers_vegan')->label('Веган')->columnSpan(1),
                        Toggle::make('offers_vegetarian')->label('Вегетарианская')->columnSpan(1),
                        Toggle::make('offers_gluten_free')->label('Без глютена')->columnSpan(1),
                        Toggle::make('offers_keto')->label('Кето')->columnSpan(1),
                        TextInput::make('avg_check_price')->label('Средний чек (₽)')->numeric()->columnSpan(1),
                        TextInput::make('signature_dish')->label('Фирменное блюдо')->maxLength(255)->columnSpan(1),
                    ]),

                Section::make('Доставка и услуги')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_delivery')->label('Доставка')->columnSpan(1),
                        TextInput::make('delivery_radius_km')->label('Радиус доставки (км)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_amount')->label('Минимальная сумма (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_time_min')->label('Время доставки (мин)')->numeric()->columnSpan(1),
                        Toggle::make('has_pickup')->label('Самовывоз')->columnSpan(1),
                        Toggle::make('has_table_reservation')->label('Бронирование столов')->columnSpan(1),
                        Toggle::make('has_catering')->label('Кейтеринг')->columnSpan(1),
                        Toggle::make('has_banquet_room')->label('Банкетный зал')->columnSpan(1),
                    ]),

                Section::make('KDS и интеграции')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_kds')->label('Kitchen Display System')->columnSpan(1),
                        Toggle::make('has_qr_menu')->label('QR-меню')->columnSpan(1),
                        Toggle::make('has_self_checkout')->label('Самообслуживание')->columnSpan(1),
                        Toggle::make('has_online_ordering')->label('Онлайн-заказы')->columnSpan(1),
                        Toggle::make('accepts_cards')->label('Приём карт')->columnSpan(1),
                        Toggle::make('has_loyalty_program')->label('Программа лояльности')->columnSpan(1),
                        TextInput::make('avg_rating')->label('Средний рейтинг')->numeric()->step(0.1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                    ]),

                Section::make('Вместимость и оборудование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_seats')->label('Всего мест')->numeric()->columnSpan(1),
                        TextInput::make('bar_seats')->label('Мест в баре')->numeric()->columnSpan(1),
                        TextInput::make('kitchen_positions')->label('Позиций на кухне')->numeric()->columnSpan(1),
                        Toggle::make('has_parking')->label('Парковка')->columnSpan(1),
                        Toggle::make('has_wifi')->label('WiFi')->columnSpan(1),
                        Toggle::make('has_outdoor_seating')->label('Летняя веранда')->columnSpan(1),
                        Toggle::make('has_smoking_area')->label('Зона курения')->columnSpan(1),
                        Toggle::make('pet_friendly')->label('Питомцы разрешены')->columnSpan(1),
                    ]),

                Section::make('Штат и сертификация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('chef_count')->label('Шефов')->numeric()->columnSpan(1),
                        TextInput::make('staff_count')->label('Сотрудников')->numeric()->columnSpan(1),
                        Toggle::make('has_michelin_stars')->label('Звёзды Мишлена')->columnSpan(1),
                        TextInput::make('michelin_stars_count')->label('Количество звёзд')->numeric()->columnSpan(1),
                        TextInput::make('health_certificate')->label('Сертификат здоровья')->columnSpan(2),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('food-logo'),
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('food-main'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('food-gallery')->columnSpan('full'),
                        FileUpload::make('menu_pdf')->label('Меню (PDF)')->acceptedFileTypes(['application/pdf'])->directory('food-menu')->columnSpan(1),
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
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('avg_check_price')->label('Средний чек (₽)')->numeric()->badge()->color('success'),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                TextColumn::make('avg_rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning'),
                BadgeColumn::make('has_qr_menu')->label('QR-меню')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('has_kds')->label('KDS')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'restaurant' => 'Ресторан',
                    'cafe' => 'Кафе',
                    'pizzeria' => 'Пиццерия',
                    'sushi' => 'Суши',
                ]),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
                Filter::make('has_kds')->query(fn (Builder $q) => $q->where('has_kds', true))->label('С KDS'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFood::route('/'),
                'create' => Pages\CreateFood::route('/create'),
                'edit' => Pages\EditFood::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
