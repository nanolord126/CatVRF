<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\GroceryAndDelivery;

use Filament\Resources\Resource;

final class GroceryAndDeliveryResource extends Resource
{

    protected static ?string $model = GroceryStore::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название магазина')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('store_code')->label('Код магазина')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('store_type')->label('Тип')->options([
                            'supermarket' => 'Супермаркет',
                            'convenience' => 'Мини-маркет',
                            'online' => 'Онлайн-доставка',
                            'specialty' => 'Специализированный',
                            'organic' => 'Эко-магазин',
                            'farmer_market' => 'Фермерский рынок'
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

                Section::make('Описание')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Ассортимент и категории')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('categories')->label('Категории товаров')->columnSpan(2),
                        Toggle::make('has_fresh_produce')->label('Свежие фрукты/овощи')->columnSpan(1),
                        Toggle::make('has_meat')->label('Мясо и рыба')->columnSpan(1),
                        Toggle::make('has_dairy')->label('Молочные продукты')->columnSpan(1),
                        Toggle::make('has_bread')->label('Хлеб и выпечка')->columnSpan(1),
                        Toggle::make('has_prepared_meals')->label('Готовые блюда')->columnSpan(1),
                        Toggle::make('has_organic')->label('Органические продукты')->columnSpan(1),
                        Toggle::make('has_ethnic_food')->label('Этнические продукты')->columnSpan(1),
                        Toggle::make('has_pet_supplies')->label('Товары для животных')->columnSpan(1),
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

                Section::make('Доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_delivery')->label('Осуществляет доставку')->columnSpan(1),
                        TextInput::make('delivery_radius_km')->label('Радиус доставки (км)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_amount')->label('Минимальная сумма заказа (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_price')->label('Стоимость доставки (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_time_min')->label('Минимальное время доставки (мин)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_time_max')->label('Максимальное время доставки (мин)')->numeric()->columnSpan(1),
                        Toggle::make('has_express_delivery')->label('Экспресс-доставка (до 30 мин)')->columnSpan(1),
                        TextInput::make('express_delivery_price')->label('Цена экспресса (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Подписки и боксы')
                    ->collapsed()
                    ->schema([
                        Repeater::make('subscription_boxes')->label('Доступные подписки')
                            ->schema([
                                TextInput::make('box_name')->label('Название')->required(),
                                TextInput::make('box_price')->label('Цена (₽)')->numeric()->required(),
                                TextInput::make('box_frequency')->label('Частота (дни)')->numeric(),
                                Textarea::make('box_description')->label('Описание')->maxLength(500)->rows(2),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Диетические предпочтения')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('offers_vegan')->label('Веган')->columnSpan(1),
                        Toggle::make('offers_vegetarian')->label('Вегетарианская')->columnSpan(1),
                        Toggle::make('offers_gluten_free')->label('Без глютена')->columnSpan(1),
                        Toggle::make('offers_sugar_free')->label('Без сахара')->columnSpan(1),
                        Toggle::make('offers_keto')->label('Кето')->columnSpan(1),
                        Toggle::make('offers_low_carb')->label('Низкоуглеводная')->columnSpan(1),
                    ]),

                Section::make('Интеграции и системы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_online_catalog')->label('Онлайн-каталог')->columnSpan(1),
                        Toggle::make('has_loyalty_program')->label('Программа лояльности')->columnSpan(1),
                        Toggle::make('accepts_cards')->label('Приём карт')->columnSpan(1),
                        Toggle::make('accepts_payment_systems')->label('Интернет-платежи')->columnSpan(1),
                        Toggle::make('has_self_checkout')->label('Самообслуживание')->columnSpan(1),
                        Toggle::make('has_mobile_app')->label('Мобильное приложение')->columnSpan(1),
                        TextInput::make('honestmark_count')->label('Кол-во товаров Честный ЗНАК')->numeric()->columnSpan(1),
                    ]),

                Section::make('Цены и скидки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('avg_price_level')->label('Уровень цен')->options([
                            'budget' => 'Бюджет',
                            'mid' => 'Средний',
                            'premium' => 'Премиум'
                        ])->columnSpan(1),
                        TextInput::make('current_discount_percent')->label('Текущая макс. скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('has_special_offers')->label('Специальные предложения')->columnSpan(1),
                        Toggle::make('has_price_matching')->label('Гарантия цены')->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('grocery-logo'),
                        FileUpload::make('store_image')->label('Фото магазина')->image()->directory('grocery-store'),
                        FileUpload::make('interior_gallery')->label('Интерьер (галерея)')->multiple()->image()->directory('grocery-interior')->columnSpan('full'),
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
                ImageColumn::make('logo')->label('Логотип')->size(40),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('store_type')->label('Тип')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                TextColumn::make('delivery_radius_km')->label('Радиус доставки (км)')->numeric(),
                TextColumn::make('delivery_time_min')->label('Время (мин)')->numeric()->toggleable(isToggledHiddenByDefault: false),
                BadgeColumn::make('has_express_delivery')->label('Экспресс')->colors(['warning' => true, 'gray' => false]),
                BadgeColumn::make('has_loyalty_program')->label('Лояльность')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('store_type')->options([
                    'supermarket' => 'Супермаркет',
                    'convenience' => 'Мини-маркет',
                    'online' => 'Онлайн-доставка',
                    'specialty' => 'Специализированный',
                    'organic' => 'Эко-магазин',
                ]),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
                Filter::make('has_express')->query(fn (Builder $q) => $q->where('has_express_delivery', true))->label('Экспресс-доставка'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListGroceryAndDelivery::route('/'),
                'create' => Pages\CreateGroceryAndDelivery::route('/create'),
                'edit' => Pages\EditGroceryAndDelivery::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
