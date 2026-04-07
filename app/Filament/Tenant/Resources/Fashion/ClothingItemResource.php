<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fashion;


use Psr\Log\LoggerInterface;
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class ClothingItemResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = ClothingItem::class;
        protected static ?string $navigationIcon = 'heroicon-m-shopping-bag';
        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-shopping-bag')
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
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        TextInput::make('brand')
                            ->label('Бренд')
                            ->required()
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('main_image')
                            ->label('Основное изображение')
                            ->image()
                            ->directory('fashion')
                            ->required()
                            ->columnSpan(1),

                        FileUpload::make('gallery')
                            ->label('Галерея')
                            ->image()
                            ->multiple()
                            ->directory('fashion')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и тип')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'mens' => 'Мужская одежда',
                                'womens' => 'Женская одежда',
                                'kids' => 'Детская одежда',
                                'unisex' => 'Унисекс',
                            ])
                            ->required()
                            ->columnSpan(1),

                        Select::make('item_type')
                            ->label('Тип товара')
                            ->options([
                                'shirt' => 'Рубашка',
                                'pants' => 'Брюки',
                                'dress' => 'Платье',
                                'jacket' => 'Куртка',
                                'sweater' => 'Свитер',
                                'shoes' => 'Обувь',
                                'accessories' => 'Аксессуары',
                            ])
                            ->required()
                            ->columnSpan(1),

                        TagsInput::make('colors')
                            ->label('Доступные цвета')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Размеры и материал')
                    ->icon('heroicon-m-square-3-stack-3d')
                    ->schema([
                        TagsInput::make('available_sizes')
                            ->label('Размеры (XS, S, M, L, XL, XXL)')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('material_composition')
                            ->label('Состав (100% cotton и т.д.)')
                            ->columnSpan(2),

                        Toggle::make('is_organic')
                            ->label('Органический материал')
                            ->columnSpan(1),

                        Toggle::make('is_eco_friendly')
                            ->label('♻️ Экологичный')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Цена и комиссия')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')
                            ->label('Цена (₽)')
                            ->numeric()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('commission_percent')
                            ->label('Комиссия (%)')
                            ->numeric()
                            ->default(14)
                            ->columnSpan(1),

                        TextInput::make('stock_quantity')
                            ->label('В наличии')
                            ->numeric()
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('low_stock_threshold')
                            ->label('Минимум для уведомления')
                            ->numeric()
                            ->default(5)
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('review_count')
                            ->label('Отзывы')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Активный')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_featured')
                            ->label('⭐ Рекомендуемый')
                            ->columnSpan(1),

                        Toggle::make('is_trending')
                            ->label('🔥 В тренде')
                            ->columnSpan(1),

                        Toggle::make('is_sale')
                            ->label('🏷️ Распродажа')
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
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')
                    ->label('Товар')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-shopping-bag')
                    ->limit(35),

                TextColumn::make('brand')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'womens' => 'Ж',
                        'kids' => 'Д',
                        'unisex' => 'У',
                        default => '-',
                    })
                    ->color(fn ($state) => match($state) {
                        'womens' => 'pink',
                        'kids' => 'green',
                        'unisex' => 'purple',
                        default => 'gray',
                    }),

                TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('stock_quantity')
                    ->label('Наличие')
                    ->numeric()
                    ->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->alignment('center')
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
                    }),

                BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемый')
                    ->toggleable(),

                BooleanColumn::make('is_trending')
                    ->label('🔥 Тренд')
                    ->toggleable(),

                BooleanColumn::make('is_sale')
                    ->label('🏷️ Распродажа')
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Активный')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Категория')
                    ->options([
                        'mens' => 'Мужская',
                        'womens' => 'Женская',
                        'kids' => 'Детская',
                        'unisex' => 'Унисекс',
                    ])
                    ->multiple(),

                SelectFilter::make('brand')
                    ->label('Бренд')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_trending')
                    ->label('В тренде'),

                Filter::make('price_budget')
                    ->label('До 5000 ₽')
                    ->query(fn (Builder $query) => $query->where('price', '<', 500000)),

                Filter::make('low_stock')
                    ->label('Мало в наличии')
                    ->query(fn (Builder $query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),

                    Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('Рекомендовать')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            $this->logger->info('Clothing featured', ['item_id' => $record->id, 'correlation_id' => $record->correlation_id]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                                $this->logger->info('Clothing bulk activated', ['item_id' => $record->id, 'correlation_id' => $record->correlation_id]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('on_sale')
                        ->label('На распродажу')
                        ->icon('heroicon-m-tag')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_sale' => true]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Fashion\Pages\ListItems::route('/'),
                'create' => \App\Filament\Tenant\Resources\Fashion\Pages\CreateItem::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Fashion\Pages\EditItem::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}
