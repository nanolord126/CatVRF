<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use Filament\Resources\Resource;

final class CosmeticProductResource extends Resource
{


    protected static ?string $model = CosmeticProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-beaker';
        protected static ?string $navigationLabel = 'Косметика';
        protected static ?string $navigationGroup = 'Beauty & Wellness';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->description('Базовые данные косметического товара')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated()
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label('Название товара')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Название косметического средства'),
                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(100)
                                ->placeholder('Артикул товара'),
                            Forms\Components\Select::make('salon_id')
                                ->label('Основной салон')
                                ->relationship('salon', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()?->id))
                                ->nullable()
                                ->preload(),
                        ])->columns(2),

                    Forms\Components\Section::make('Описание и характеристики')
                        ->description('Подробная информация о товаре')
                        ->schema([
                            Forms\Components\RichEditor::make('description')
                                ->label('Описание')
                                ->columnSpanFull()
                                ->placeholder('Описание косметического средства'),
                            Forms\Components\TagsInput::make('ingredients')
                                ->label('Ингредиенты')
                                ->placeholder('Добавьте компоненты')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('brand')
                                ->label('Бренд')
                                ->maxLength(255)
                                ->nullable(),
                            Forms\Components\TextInput::make('volume')
                                ->label('Объём')
                                ->maxLength(50)
                                ->nullable()
                                ->placeholder('Например: 50 мл'),
                            Forms\Components\Select::make('product_type')
                                ->label('Тип продукта')
                                ->options([
                                    'foundation' => 'Тональная основа',
                                    'powder' => 'Пудра',
                                    'lipstick' => 'Помада',
                                    'eyeshadow' => 'Тени',
                                    'mascara' => 'Тушь',
                                    'concealer' => 'Консилер',
                                    'blush' => 'Румяна',
                                    'skincare' => 'Уход за кожей',
                                    'other' => 'Другое',
                                ])
                                ->nullable(),
                        ])->columns(2),

                    Forms\Components\Section::make('Цены и финансы')
                        ->description('Установка цен и комиссий')
                        ->schema([
                            Forms\Components\TextInput::make('price')
                                ->label('Цена (рубли)')
                                ->numeric()
                                ->required()
                                ->step(0.01)
                                ->prefix('₽'),
                            Forms\Components\TextInput::make('cost_price')
                                ->label('Себестоимость (рубли)')
                                ->numeric()
                                ->step(0.01)
                                ->nullable()
                                ->prefix('₽'),
                            Forms\Components\TextInput::make('discount_percent')
                                ->label('Скидка (%)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01),
                            Forms\Components\Select::make('commission_type')
                                ->label('Тип комиссии')
                                ->options([
                                    'percent' => 'Процент',
                                    'fixed' => 'Фиксированная сумма',
                                ])
                                ->default('percent'),
                        ])->columns(2),

                    Forms\Components\Section::make('Инвентарь')
                        ->description('Управление запасами')
                        ->schema([
                            Forms\Components\TextInput::make('current_stock')
                                ->label('Текущий остаток')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->minValue(0),
                            Forms\Components\TextInput::make('min_stock_threshold')
                                ->label('Минимальный остаток')
                                ->numeric()
                                ->default(10)
                                ->minValue(0),
                            Forms\Components\TextInput::make('max_stock_threshold')
                                ->label('Максимальный остаток')
                                ->numeric()
                                ->nullable()
                                ->minValue(0),
                        ])->columns(3),

                    Forms\Components\Section::make('Медиа')
                        ->description('Фотографии и изображения')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->label('Основное изображение')
                                ->image()
                                ->directory('cosmetics')
                                ->visibility('public'),
                            Forms\Components\FileUpload::make('gallery_images')
                                ->label('Галерея изображений')
                                ->multiple()
                                ->image()
                                ->directory('cosmetics/gallery')
                                ->visibility('public')
                                ->columnSpanFull(),
                        ])->columns(1),

                    Forms\Components\Section::make('Статус')
                        ->description('Состояние товара на витрине')
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Товар активен')
                                ->default(true),
                            Forms\Components\Toggle::make('is_featured')
                                ->label('Рекомендуемый товар')
                                ->default(false),
                            Forms\Components\Toggle::make('is_sale')
                                ->label('На распродаже')
                                ->default(false),
                        ])->columns(3),

                    Forms\Components\Section::make('Рейтинг и отзывы')
                        ->description('Статистика товара')
                        ->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг')
                                ->numeric()
                                ->disabled()
                                ->default(0),
                            Forms\Components\TextInput::make('review_count')
                                ->label('Количество отзывов')
                                ->numeric()
                                ->disabled()
                                ->default(0),
                        ])->columns(2),

                    Forms\Components\Hidden::make('tenant_id')
                        ->default(fn () => filament()->getTenant()?->id),
                    Forms\Components\Hidden::make('correlation_id')
                        ->default(fn () => (string) Str::uuid()),
                    Forms\Components\Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\ImageColumn::make('image')
                        ->label('Фото')
                        ->size(50),
                    Tables\Columns\TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable()
                        ->weight('bold')
                        ->limit(40),
                    Tables\Columns\TextColumn::make('sku')
                        ->label('SKU')
                        ->searchable()
                        ->copyable(),
                    Tables\Columns\TextColumn::make('brand')
                        ->label('Бренд')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('product_type')
                        ->label('Тип')
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'powder' => 'Пудра',
                            'lipstick' => 'Помада',
                            'eyeshadow' => 'Тени',
                            'mascara' => 'Тушь',
                            'concealer' => 'Консилер',
                            'blush' => 'Румяна',
                            'skincare' => 'Уход',
                            default => $state,
                        }),
                    Tables\Columns\TextColumn::make('price')
                        ->label('Цена')
                        ->money('RUB', locale: 'ru_RU')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('current_stock')
                        ->label('Остаток')
                        ->numeric()
                        ->sortable()
                        ->color(fn ($state) => match (true) {
                            $state <= 0 => 'danger',
                            $state <= 10 => 'warning',
                            default => 'success',
                        }),
                    Tables\Columns\TextColumn::make('rating')
                        ->label('Рейтинг')
                        ->badge()
                        ->numeric(1)
                        ->sortable()
                        ->color(fn ($state) => match (true) {
                            $state >= 4.5 => 'success',
                            $state >= 3.5 => 'info',
                            default => 'warning',
                        }),
                    Tables\Columns\IconColumn::make('is_active')
                        ->label('Активен')
                        ->boolean()
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_featured')
                        ->label('Рекомендуемый')
                        ->boolean()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата добавления')
                        ->date()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('product_type')
                        ->label('Тип продукта')
                        ->options([
                            'foundation' => 'Тональная основа',
                            'powder' => 'Пудра',
                            'lipstick' => 'Помада',
                            'eyeshadow' => 'Тени',
                            'mascara' => 'Тушь',
                            'concealer' => 'Консилер',
                            'blush' => 'Румяна',
                            'skincare' => 'Уход за кожей',
                        ]),
                    Tables\Filters\TernaryFilter::make('is_active')
                        ->label('Активен'),
                    Tables\Filters\TernaryFilter::make('is_featured')
                        ->label('Рекомендуемый'),
                    Tables\Filters\Filter::make('stock')
                        ->label('Остаток в наличии')
                        ->query(fn (Builder $query) => $query->where('current_stock', '>', 0)),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                        Tables\Actions\BulkAction::make('activate')
                            ->label('Активировать')
                            ->icon('heroicon-o-check')
                            ->action(fn ($records) => $records->each->update(['is_active' => true])),
                        Tables\Actions\BulkAction::make('deactivate')
                            ->label('Деактивировать')
                            ->icon('heroicon-o-x-mark')
                            ->action(fn ($records) => $records->each->update(['is_active' => false])),
                        Tables\Actions\BulkAction::make('feature')
                            ->label('Добавить в рекомендуемые')
                            ->icon('heroicon-o-star')
                            ->action(fn ($records) => $records->each->update(['is_featured' => true])),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()?->id);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListCosmeticProducts::route('/'),
                'create' => Pages\CreateCosmeticProduct::route('/create'),
                'view' => Pages\ViewCosmeticProduct::route('/{record}'),
                'edit' => Pages\EditCosmeticProduct::route('/{record}/edit'),
            ];
        }
}
