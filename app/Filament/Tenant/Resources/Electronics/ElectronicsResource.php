<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Electronics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicsResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = ElectronicProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-bolt';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название продукта')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('model')->label('Модель')->maxLength(255)->columnSpan(1),
                        Select::make('category')->label('Категория')->options([
                            'smartphones' => 'Смартфоны',
                            'laptops' => 'Ноутбуки',
                            'tablets' => 'Планшеты',
                            'wearables' => 'Носимые',
                            'audio' => 'Аудио',
                            'accessories' => 'Аксессуары',
                        ])->required()->columnSpan(1),
                        TextInput::make('brand')->label('Бренд')->required()->maxLength(100)->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Характеристики')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('processor')->label('Процессор')->columnSpan(1),
                        TextInput::make('ram')->label('ОЗУ (ГБ)')->numeric()->columnSpan(1),
                        TextInput::make('storage')->label('Хранилище (ГБ)')->numeric()->columnSpan(1),
                        TextInput::make('display_size')->label('Диагональ экрана (дюйм)')->numeric()->columnSpan(1),
                        TextInput::make('resolution')->label('Разрешение')->columnSpan(1),
                        TextInput::make('battery_capacity')->label('Батарея (мАч)')->numeric()->columnSpan(1),
                        TextInput::make('weight_grams')->label('Вес (г)')->numeric()->columnSpan(1),
                        TextInput::make('dimensions')->label('Размеры (мм)')->columnSpan(1),
                        TagsInput::make('features')->label('Особенности')->columnSpan(2),
                    ]),

                Section::make('Цена и доступность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('in_stock')->label('В наличии')->columnSpan(1),
                        TextInput::make('stock_quantity')->label('Количество')->numeric()->columnSpan(1),
                        TextInput::make('warehouse_quantity')->label('На складе')->numeric()->columnSpan(1),
                        TextInput::make('pre_order_quantity')->label('Предзаказы')->numeric()->columnSpan(1),
                    ]),

                Section::make('Гарантия и обслуживание')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('warranty_months')->label('Гарантия (месяцев)')->numeric()->columnSpan(1),
                        Toggle::make('has_extended_warranty')->label('Расширенная гарантия')->columnSpan(1),
                        TextInput::make('warranty_price')->label('Цена расширенной (₽)')->numeric()->columnSpan(1),
                        Toggle::make('has_trade_in')->label('Trade-in программа')->columnSpan(1),
                        Toggle::make('has_insurance')->label('Страховка')->columnSpan(1),
                        TextInput::make('insurance_price')->label('Цена страховки (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Доставка и возврат')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_free_shipping')->label('Бесплатная доставка')->columnSpan(1),
                        TextInput::make('shipping_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                        TextInput::make('return_days')->label('Возврат (дней)')->numeric()->columnSpan(1),
                        Toggle::make('accepts_returns')->label('Принимает возвраты')->columnSpan(1),
                    ]),

                Section::make('Интеграции и платформы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('yandex_market_url')->label('Яндекс.Маркет')->url()->columnSpan(2),
                        TextInput::make('amazon_url')->label('Amazon')->url()->columnSpan(2),
                        TextInput::make('wildberries_url')->label('WildBerries')->url()->columnSpan(2),
                        TagsInput::make('available_platforms')->label('Доступные платформы')->columnSpan(2),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('electronics-main'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('electronics-gallery')->columnSpan('full'),
                        FileUpload::make('comparison_chart')->label('Таблица сравнения')->image()->directory('electronics-compare'),
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
                        Toggle::make('is_active')->label('Активен')->default(true),
                        Toggle::make('is_featured')->label('Избранный')->default(false),
                        Toggle::make('verified')->label('Проверен')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('main_image')->label('Фото')->size(50),
                TextColumn::make('name')->label('Продукт')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('brand')->label('Бренд')->searchable()->sortable(),
                TextColumn::make('category')->label('Категория')->badge()->color('info'),
                TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
                BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_extended_warranty')->label('Расш. гарант.')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранный')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('warranty_months')->label('Гарант. (мес)')->numeric()->badge(),
                TextColumn::make('stock_quantity')->label('Кол-во')->numeric()->badge()->color('secondary'),
                TextColumn::make('sku')->label('SKU')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('category')->options([
                    'smartphones' => 'Смартфоны',
                    'laptops' => 'Ноутбуки',
                    'tablets' => 'Планшеты',
                    'wearables' => 'Носимые',
                    'audio' => 'Аудио',
                    'accessories' => 'Аксессуары',
                ]),
                SelectFilter::make('brand'),
                Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
                Filter::make('has_warranty')->query(fn (Builder $q) => $q->where('warranty_months', '>', 0))->label('С гарантией'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListElectronics::route('/'),
                'create' => Pages\CreateElectronics::route('/create'),
                'edit' => Pages\EditElectronics::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
