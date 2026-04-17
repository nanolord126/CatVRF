<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HouseholdGoods;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter, Filter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class HouseholdGoodsResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = \App\Domains\HouseholdGoods\Models\HouseholdGoods::class;
        protected static ?string $navigationIcon = 'heroicon-o-home-modern';
        protected static ?string $navigationGroup = 'HouseholdGoods';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->description('Описание товара')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        TextInput::make('barcode')
                            ->label('Штрихкод')
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('main_photo')
                            ->label('Главное фото')
                            ->image()
                            ->directory('household')
                            ->columnSpan(1),

                        FileUpload::make('photos')
                            ->label('Галерея')
                            ->image()
                            ->multiple()
                            ->directory('household')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и характеристики')
                    ->icon('heroicon-m-tag')
                    ->description('Классификация товара')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'kitchen' => '🍴 Кухня',
                                'bedroom' => '🛏️ Спальня',
                                'bathroom' => '🚿 Ванная',
                                'living_room' => '🛋️ Гостиная',
                                'storage' => '📦 Хранение',
                                'lighting' => '💡 Освещение',
                                'textile' => '🧵 Текстиль',
                                'decoration' => '🎨 Декор',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('subcategory')
                            ->label('Подкатегория')
                            ->searchable()
                            ->columnSpan(2),

                        TextInput::make('brand')
                            ->label('Бренд')
                            ->columnSpan(1),

                        TextInput::make('manufacturer_country')
                            ->label('Страна производства')
                            ->columnSpan(1),

                        TagsInput::make('features')
                            ->label('Характеристики')
                            ->columnSpan('full'),

                        Select::make('material')
                            ->label('Материал')
                            ->options([
                                'plastic' => 'Пластик',
                                'wood' => 'Дерево',
                                'metal' => 'Металл',
                                'ceramic' => 'Керамика',
                                'glass' => 'Стекло',
                                'fabric' => 'Ткань',
                                'silicone' => 'Силикон',
                                'stainless_steel' => 'Нержавейка',
                            ])
                            ->columnSpan(2),

                        Select::make('color')
                            ->label('Цвет')
                            ->options([
                                'white' => 'Белый',
                                'black' => 'Чёрный',
                                'gray' => 'Серый',
                                'brown' => 'Коричневый',
                                'wood' => 'Дерево',
                                'colorful' => 'Разноцветный',
                            ])
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и наличие')
                    ->icon('heroicon-m-banknote')
                    ->description('Стоимость и запасы')
                    ->schema([
                        TextInput::make('price')
                            ->label('Цена (₽)')
                            ->numeric()
                            ->required()
                            ->suffix('₽')
                            ->columnSpan(2),

                        TextInput::make('cost')
                            ->label('Себестоимость (₽)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('current_stock')
                            ->label('На складе')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('min_stock_threshold')
                            ->label('Минимум')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1),

                        TextInput::make('max_stock_threshold')
                            ->label('Максимум')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1),

                        Select::make('stock_status')
                            ->label('Статус запаса')
                            ->options([
                                'in_stock' => '✓ В наличии',
                                'low_stock' => '⚠️ Мало',
                                'out_of_stock' => '❌ Нет в наличии',
                                'on_order' => '📦 В заказе',
                            ])
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Размер и вес')
                    ->icon('heroicon-m-square-3-stack-3d')
                    ->description('Физические параметры')
                    ->schema([
                        TextInput::make('length_cm')
                            ->label('Длина (см)')
                            ->numeric()
                            ->columnSpan(1),

                        TextInput::make('width_cm')
                            ->label('Ширина (см)')
                            ->numeric()
                            ->columnSpan(1),

                        TextInput::make('height_cm')
                            ->label('Высота (см)')
                            ->numeric()
                            ->columnSpan(1),

                        TextInput::make('weight_kg')
                            ->label('Вес (кг)')
                            ->numeric()
                            ->columnSpan(1),

                        TextInput::make('volume_liters')
                            ->label('Объём (л)')
                            ->numeric()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг и отзывы')
                    ->icon('heroicon-m-star')
                    ->description('Оценки товара')
                    ->schema([
                        TextInput::make('rating')
                            ->label('Рейтинг (0-5)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('review_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('purchase_count')
                            ->label('Куплено раз')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('view_count')
                            ->label('Просмотров')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->description('Видимость и статус')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_featured')
                            ->label('⭐ Рекомендуемый')
                            ->columnSpan(1),

                        Toggle::make('is_new')
                            ->label('🆕 Новинка')
                            ->columnSpan(1),

                        Toggle::make('is_sale')
                            ->label('🔥 Распродажа')
                            ->columnSpan(1),
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

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table->columns([
                ImageColumn::make('main_photo')
                    ->label('Фото')
                    ->height(50),

                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                BadgeColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'bedroom' => 'Спальня',
                        'bathroom' => 'Ванная',
                        'living_room' => 'Гостиная',
                        default => $state,
                    })
                    ->color('info'),

                TextColumn::make('brand')
                    ->label('Бренд')
                    ->searchable(),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                BadgeColumn::make('stock_status')
                    ->label('Запас')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low_stock' => 'Мало',
                        'out_of_stock' => 'Нет',
                        'on_order' => 'В заказе',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'on_order' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))
                    ->badge()
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state >= 3 ? 'warning' : 'danger')),

                TextColumn::make('purchase_count')
                    ->label('Куплено')
                    ->numeric()
                    ->alignment('center'),

                BooleanColumn::make('is_featured')
                    ->label('⭐')
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Активен')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'kitchen' => 'Кухня',
                        'bedroom' => 'Спальня',
                        'bathroom' => 'Ванная',
                        'living_room' => 'Гостиная',
                    ])
                    ->multiple(),

                SelectFilter::make('stock_status')
                    ->label('Запас')
                    ->options([
                        'in_stock' => 'В наличии',
                        'low_stock' => 'Мало',
                        'out_of_stock' => 'Нет',
                    ])
                    ->multiple(),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_active')
                    ->label('Активен'),

                Filter::make('high_rating')
                    ->label('Рейтинг ≥4.0')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $this->logger->info('Household good bulk deleted', [
                                    'good_id' => $record->id,
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
                                $this->logger->info('Household good activated', [
                                    'good_id' => $record->id,
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

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\HouseholdGoods\HouseholdGoodsResource\Pages\ListHouseholdGoods::route('/'),
                'create' => \App\Filament\Tenant\Resources\HouseholdGoods\HouseholdGoodsResource\Pages\CreateHouseholdGoods::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\HouseholdGoods\HouseholdGoodsResource\Pages\EditHouseholdGoods::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}
