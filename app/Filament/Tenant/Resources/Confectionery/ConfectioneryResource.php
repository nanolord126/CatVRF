<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConfectioneryResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = ConfectioneryShop::class;
        protected static ?string $navigationIcon = 'heroicon-o-cake';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название пекарни')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'bakery' => 'Пекарня',
                            'pastry' => 'Кондитерская',
                            'cake_shop' => 'Торты на заказ',
                            'combined' => 'Комбинированная'
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
                        TagsInput::make('specializations')->label('Специализация (эклеры, капкейки, бисквиты)')->columnSpan('full'),
                    ]),

                Section::make('Ассортимент и производство')
                    ->columns(2)
                    ->schema([
                        Toggle::make('makes_fresh_bread')->label('Свежий хлеб')->columnSpan(1),
                        Toggle::make('makes_pastries')->label('Выпечка')->columnSpan(1),
                        Toggle::make('makes_custom_cakes')->label('Торты на заказ')->columnSpan(1),
                        Toggle::make('makes_cupcakes')->label('Капкейки')->columnSpan(1),
                        Toggle::make('vegan_options')->label('Веган-опции')->columnSpan(1),
                        Toggle::make('gluten_free_options')->label('Без глютена')->columnSpan(1),
                        TagsInput::make('popular_items')->label('Популярные позиции')->columnSpan(2),
                    ]),

                Section::make('Режим работы и доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('08:00-20:00')->columnSpan(2),
                        Toggle::make('has_delivery')->label('Доставка')->columnSpan(1),
                        TextInput::make('delivery_time_min')->label('Минимальное время доставки (мин)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_for_delivery')->label('Минимальный заказ для доставки (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_area_km')->label('Зона доставки (км)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Кастомные торты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('accepts_custom_orders')->label('Принимает заказы')->columnSpan(2),
                        TextInput::make('custom_cake_min_price')->label('Минимальная цена торта (₽)')->numeric()->columnSpan(1),
                        TextInput::make('custom_cake_lead_time')->label('Время выполнения (дней)')->numeric()->columnSpan(1),
                        TextInput::make('max_custom_orders_per_week')->label('Максимум заказов в неделю')->numeric()->columnSpan(1),
                        TagsInput::make('cake_styles')->label('Стили оформления')->columnSpan(2),
                    ]),

                Section::make('Работники и оборудование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('baker_count')->label('Количество пекарей')->numeric()->columnSpan(1),
                        TextInput::make('decorator_count')->label('Количество декораторов')->numeric()->columnSpan(1),
                        TextInput::make('production_capacity_kg')->label('Суточная мощность (кг)')->numeric()->columnSpan(1),
                        Toggle::make('has_oven')->label('Есть печь')->columnSpan(1),
                        Toggle::make('eco_ingredients')->label('Эко-ингредиенты')->columnSpan(2),
                    ]),

                Section::make('Социальные сети и контакты')
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
                        FileUpload::make('logo')->label('Логотип')->image()->directory('confectionery-logos'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('confectionery-gallery')->columnSpan('full'),
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
                TextColumn::make('name')->label('Пекарня')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(18),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('verified')->label('Проверена')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('accepts_custom_orders')->label('Кастомные торты')->colors(['info' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->sortable()->badge(),
                TextColumn::make('website')->label('Веб-сайт')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'bakery' => 'Пекарня',
                    'pastry' => 'Кондитерская',
                    'cake_shop' => 'Торты на заказ',
                    'combined' => 'Комбинированная'
                ]),
                Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true)),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
                Filter::make('accepts_custom')->query(fn (Builder $q) => $q->where('accepts_custom_orders', true))->label('Кастомные торты'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListConfectionery::route('/'),
                'create' => Pages\CreateConfectionery::route('/create'),
                'edit' => Pages\EditConfectionery::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
