<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Florist;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FloristResource extends Resource
{

    protected static ?string $model = FloristShop::class;
        protected static ?string $navigationIcon = 'heroicon-o-sparkles';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название флориста')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'flower_shop' => 'Цветочный магазин',
                            'delivery_service' => 'Служба доставки',
                            'event_florist' => 'Флорист для мероприятий',
                            'subscription' => 'Подписка на цветы'
                        ])->required()->columnSpan(1),
                    ]),

                Section::make('Адрес и геолокация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание и специализация')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('specializations')->label('Специализация')->columnSpan('full'),
                    ]),

                Section::make('Услуги и товары')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_bouquets')->label('Букеты')->columnSpan(1),
                        Toggle::make('has_arrangements')->label('Композиции')->columnSpan(1),
                        Toggle::make('has_wedding_flowers')->label('Свадебные цветы')->columnSpan(1),
                        Toggle::make('has_subscription_service')->label('Подписка')->columnSpan(1),
                        Toggle::make('accepts_custom_orders')->label('Кастомные заказы')->columnSpan(1),
                        Toggle::make('offers_same_day_delivery')->label('Доставка в день заказа')->columnSpan(1),
                        TagsInput::make('flower_types')->label('Виды цветов')->columnSpan(2),
                    ]),

                Section::make('Доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_delivery')->label('Доставка')->columnSpan(2),
                        TextInput::make('delivery_time_min')->label('Минимальное время (мин)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_for_delivery')->label('Минимальный заказ (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_area_km')->label('Зона доставки (км)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_cost')->label('Цена доставки (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Режим работы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('09:00-20:00')->columnSpan(2),
                        Toggle::make('works_weekends')->label('Работает в выходные')->columnSpan(1),
                        Toggle::make('works_holidays')->label('Работает в праздники')->columnSpan(1),
                    ]),

                Section::make('Оборудование и команда')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('florist_count')->label('Количество флористов')->numeric()->columnSpan(1),
                        TextInput::make('daily_capacity')->label('Суточная вместимость (заказов)')->numeric()->columnSpan(1),
                        Toggle::make('has_refrigeration')->label('Холодильная установка')->columnSpan(1),
                        Toggle::make('has_workshop')->label('Мастерская')->columnSpan(1),
                    ]),

                Section::make('Контакты и социальные сети')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp')->label('WhatsApp')->tel()->columnSpan(1),
                        TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                        TextInput::make('instagram')->label('Instagram')->url()->columnSpan(1),
                        TextInput::make('vk')->label('VK')->url()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('florist-logos'),
                        FileUpload::make('gallery')->label('Галерея работ')->multiple()->image()->directory('florist-gallery')->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('keywords')->label('Ключевые слова')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активна')->default(true),
                        Toggle::make('is_featured')->label('Избранная')->default(false),
                        Toggle::make('verified')->label('Проверена')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('logo')->label('Логотип')->size(50),
                TextColumn::make('name')->label('Флорист')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(18),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('accepts_custom_orders')->label('Кастомные')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->badge(),
                TextColumn::make('website')->label('Веб-сайт')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'flower_shop' => 'Цветочный магазин',
                    'delivery_service' => 'Служба доставки',
                    'event_florist' => 'Флорист для мероприятий',
                    'subscription' => 'Подписка на цветы'
                ]),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFlorist::route('/'),
                'create' => Pages\CreateFlorist::route('/create'),
                'edit' => Pages\EditFlorist::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
