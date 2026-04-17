<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Stationery;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class StationeryResource extends Resource
{

    protected static ?string $model = StationeryProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-pencil';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название товара')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('barcode')->label('Штрихкод')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('category')->label('Категория')->options([
                            'paper' => 'Бумага',
                            'pens' => 'Ручки',
                            'pencils' => 'Карандаши',
                            'notebooks' => 'Тетради',
                            'folders' => 'Папки',
                            'office_supplies' => 'Офис-принадлежности',
                            'art_supplies' => 'Художественные',
                            'sticky_notes' => 'Стикеры'
                        ])->required()->columnSpan(1),
                        TextInput::make('brand')->label('Бренд')->maxLength(100)->columnSpan(1),
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
                        TextInput::make('color')->label('Цвет')->columnSpan(1),
                        TextInput::make('size')->label('Размер')->columnSpan(1),
                        TextInput::make('material')->label('Материал')->columnSpan(1),
                        TextInput::make('quantity_per_pack')->label('Штук в упаковке')->numeric()->columnSpan(1),
                        TextInput::make('weight_grams')->label('Вес (г)')->numeric()->columnSpan(1),
                        TextInput::make('dimensions')->label('Размеры (см)')->columnSpan(1),
                        TagsInput::make('features')->label('Особенности')->columnSpan(2),
                    ]),

                Section::make('Цена и запасы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')->label('Розница (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('wholesale_price')->label('Опт (₽)')->numeric()->columnSpan(1),
                        TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('in_stock')->label('В наличии')->columnSpan(1),
                        TextInput::make('stock_quantity')->label('Остаток')->numeric()->columnSpan(1),
                        TextInput::make('min_order_qty')->label('Минимальный заказ')->numeric()->columnSpan(1),
                    ]),

                Section::make('Доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_free_shipping')->label('Бесплатная доставка')->columnSpan(1),
                        TextInput::make('shipping_days')->label('Срок доставки (дн)')->numeric()->columnSpan(1),
                        TextInput::make('shipping_cost')->label('Стоимость доставки (₽)')->numeric()->columnSpan(1),
                        TextInput::make('box_quantity')->label('Штук в коробке')->numeric()->columnSpan(1),
                    ]),

                Section::make('Экология и стандарты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_eco_friendly')->label('Эко-товар')->columnSpan(1),
                        Toggle::make('is_recyclable')->label('Перерабатываемый')->columnSpan(1),
                        Toggle::make('is_fsc_certified')->label('FSC сертифицирован')->columnSpan(1),
                        Toggle::make('is_latex_free')->label('Без латекса')->columnSpan(1),
                    ]),

                Section::make('Оптовые предложения')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('wholesale_min_quantity')->label('Опт от (шт)')->numeric()->columnSpan(1),
                        TextInput::make('bulk_discount_percent')->label('Оптовая скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('has_corporate_program')->label('Корпоративная программа')->columnSpan(1),
                        TextInput::make('corporate_discount')->label('Корпоративная скидка (%)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('stationery-main'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('stationery-gallery')->columnSpan('full'),
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
                TextColumn::make('name')->label('Товар')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('brand')->label('Бренд')->searchable(),
                TextColumn::make('category')->label('Категория')->badge()->color('info'),
                TextColumn::make('price')->label('Цена (₽)')->numeric()->sortable()->badge()->color('success'),
                BadgeColumn::make('in_stock')->label('В наличии')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_eco_friendly')->label('Эко')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранный')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('stock_quantity')->label('Кол-во')->numeric()->badge(),
                TextColumn::make('sku')->label('SKU')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wholesale_price')->label('Опт (₽)')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('category')->options([
                    'paper' => 'Бумага',
                    'pens' => 'Ручки',
                    'pencils' => 'Карандаши',
                    'notebooks' => 'Тетради',
                    'folders' => 'Папки',
                    'office_supplies' => 'Офис-принадлежности',
                    'art_supplies' => 'Художественные',
                    'sticky_notes' => 'Стикеры'
                ]),
                Filter::make('in_stock')->query(fn (Builder $q) => $q->where('in_stock', true)),
                Filter::make('eco')->query(fn (Builder $q) => $q->where('is_eco_friendly', true))->label('Эко-товары'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListStationery::route('/'),
                'create' => Pages\CreateStationery::route('/create'),
                'edit' => Pages\EditStationery::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
