<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fashion;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\Filter, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class FashionProductResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = FashionProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Fashion & Style';
        protected static ?string $label = 'Товары';
        protected static ?string $pluralLabel = 'Товары';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-tag')
                    ->description('Базовые данные товара')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique('fashion_products', 'sku', ignoreRecord: true)
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->columnSpan(2),

                        Select::make('brand_id')
                            ->label('Бренд')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->columnSpan(2),

                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(2),

                        Select::make('subcategory_id')
                            ->label('Подкатегория')
                            ->relationship('subcategory', 'name')
                            ->searchable()
                            ->columnSpan(2),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('main_image')
                            ->label('Основное фото')
                            ->image()
                            ->directory('fashion')
                            ->required()
                            ->columnSpan(1),

                        FileUpload::make('gallery_images')
                            ->label('Галерея')
                            ->image()
                            ->multiple()
                            ->directory('fashion')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Описание товара')
                    ->icon('heroicon-m-document-text')
                    ->description('Материал, уход, характеристики')
                    ->schema([
                        Textarea::make('material')
                            ->label('Материал')
                            ->rows(2)
                            ->columnSpan(2),

                        Textarea::make('care_instructions')
                            ->label('Инструкция по уходу')
                            ->rows(2)
                            ->columnSpan(2),

                        TextInput::make('color')
                            ->label('Основной цвет')
                            ->columnSpan(1),

                        TextInput::make('pattern')
                            ->label('Паттерн')
                            ->columnSpan(1),

                        TextInput::make('style')
                            ->label('Стиль')
                            ->columnSpan(1),

                        TextInput::make('season')
                            ->label('Сезон')
                            ->columnSpan(1),

                        TagsInput::make('available_colors')
                            ->label('Доступные цвета')
                            ->columnSpan('full'),

                        TagsInput::make('tags')
                            ->label('Теги')
                            ->columnSpan('full'),
                    ])->columns(4),

                Section::make('Размеры и варианты')
                    ->icon('heroicon-m-squares-2x2')
                    ->description('Размерная сетка и варианты')
                    ->schema([
                        Repeater::make('variants')
                            ->label('Варианты размеров')
                            ->relationship()
                            ->schema([
                                Select::make('size')
                                    ->label('Размер (XS-XXL)')
                                    ->options([
                                        'XS' => 'XS (очень маленький)',
                                        'S' => 'S (маленький)',
                                        'M' => 'M (средний)',
                                        'L' => 'L (большой)',
                                        'XL' => 'XL (очень большой)',
                                        'XXL' => 'XXL (огромный)',
                                    ])
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('sku_variant')
                                    ->label('SKU варианта')
                                    ->columnSpan(1),

                                TextInput::make('price')
                                    ->label('Цена (₽)')
                                    ->numeric()
                                    ->required()
                                    ->suffix('₽')
                                    ->columnSpan(1),

                                TextInput::make('stock')
                                    ->label('Остаток')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true)
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
                            ->columnSpan('full'),
                    ])->columnSpan('full'),

                Section::make('Цены и комиссии')
                    ->icon('heroicon-m-banknote')
                    ->description('Финансовые параметры')
                    ->schema([
                        TextInput::make('price_base')
                            ->label('Базовая цена (₽)')
                            ->numeric()
                            ->required()
                            ->suffix('₽')
                            ->columnSpan(2),

                        TextInput::make('discount_percent')
                            ->label('Скидка (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->columnSpan(1),

                        TextInput::make('final_price')
                            ->label('Финальная цена (₽)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('commission_percent')
                            ->label('Комиссия платформы (%)')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('cost_price')
                            ->label('Себестоимость (₽)')
                            ->numeric()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг и отзывы')
                    ->icon('heroicon-m-star')
                    ->description('Оценки пользователей')
                    ->schema([
                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('review_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('return_rate')
                            ->label('% возвратов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Статус и управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->description('Видимость и активность')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_featured')
                            ->label('⭐ Рекомендуемый')
                            ->columnSpan(1),

                        Toggle::make('is_verified')
                            ->label('✓ Проверен')
                            ->columnSpan(1),

                        Toggle::make('is_new')
                            ->label('🆕 Новинка')
                            ->columnSpan(1),

                        TextInput::make('position')
                            ->label('Позиция в каталоге')
                            ->numeric()
                            ->columnSpan(2),
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

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-tag')
                    ->limit(40),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->limit(15),

                TextColumn::make('brand.name')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Категория')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('color')
                    ->label('Цвет')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('style')
                    ->label('Стиль')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_base')
                    ->label('Цена (₽)')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                TextColumn::make('discount_percent')
                    ->label('Скидка (%)')
                    ->alignment('center'),

                TextColumn::make('final_price')
                    ->label('Финал (₽)')
                    ->money('RUB', divideBy: 100)
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
                    })
                    ->sortable(),

                TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->numeric()
                    ->alignment('center'),

                TextColumn::make('return_rate')
                    ->label('Возвраты (%)')
                    ->alignment('center'),

                BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемый')
                    ->toggleable(),

                BooleanColumn::make('is_verified')
                    ->label('✓ Проверен')
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Активен')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Бренд')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('color')
                    ->label('Цвет')
                    ->distinct()
                    ->multiple(),

                SelectFilter::make('style')
                    ->label('Стиль')
                    ->distinct()
                    ->multiple(),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_verified')
                    ->label('Проверен'),

                TernaryFilter::make('is_active')
                    ->label('Активен'),

                Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                Filter::make('discounted')
                    ->label('Со скидкой')
                    ->query(fn (Builder $query) => $query->where('discount_percent', '>', 0)),

                Filter::make('high_return_rate')
                    ->label('Высокий % возвратов (>15%)')
                    ->query(fn (Builder $query) => $query->where('return_rate', '>', 15)),

                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),

                    Action::make('verify')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => !$record->is_verified)
                        ->action(function ($record) {
                            $record->update(['is_verified' => true]);
                            $this->logger->info('Fashion product verified', [
                                'product_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('В рекомендуемые')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            $this->logger->info('Fashion product featured', [
                                'product_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $this->logger->info('Fashion product bulk deleted', [
                                    'product_id' => $record->id,
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
                                $this->logger->info('Fashion product activated', [
                                    'product_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('verify')
                        ->label('Подтвердить')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_verified' => true]);
                                $this->logger->info('Fashion product bulk verified', [
                                    'product_id' => $record->id,
                                    'user_id' => $this->guard->id(),
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

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Fashion\FashionProductResource\Pages\ListFashionProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Fashion\FashionProductResource\Pages\CreateFashionProduct::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Fashion\FashionProductResource\Pages\ViewFashionProduct::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Fashion\FashionProductResource\Pages\EditFashionProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}
