<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Sports;


use Psr\Log\LoggerInterface;
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class SportEquipmentResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = SportEquipment::class;
        protected static ?string $navigationIcon = 'heroicon-m-heart';
        protected static ?string $navigationGroup = 'Sports';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-heart')
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
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('main_image')
                            ->label('Изображение')
                            ->image()
                            ->directory('sports')
                            ->columnSpan(1),

                        FileUpload::make('gallery')
                            ->label('Галерея')
                            ->image()
                            ->multiple()
                            ->directory('sports')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('sport_type')
                            ->label('Вид спорта')
                            ->options([
                                'football' => '⚽ Футбол',
                                'basketball' => '🏀 Баскетбол',
                                'tennis' => '🎾 Теннис',
                                'volleyball' => '🏐 Волейбол',
                                'swimming' => '🏊 Плавание',
                                'running' => '🏃 Бег',
                                'cycling' => '🚴 Велоспорт',
                                'fitness' => '💪 Фитнес',
                                'other' => 'Прочее',
                            ])
                            ->required()
                            ->columnSpan(1),

                        Select::make('equipment_type')
                            ->label('Тип снаряжения')
                            ->options([
                                'ball' => 'Мяч',
                                'racket' => 'Ракетка',
                                'protective' => 'Защита',
                                'apparel' => 'Одежда',
                                'shoes' => 'Обувь',
                                'accessories' => 'Аксессуары',
                            ])
                            ->required()
                            ->columnSpan(1),

                        TagsInput::make('sizes')
                            ->label('Размеры')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и наличие')
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
                            ->label('Минимум')
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

                        Toggle::make('is_bestseller')
                            ->label('🏆 Бестселлер')
                            ->columnSpan(1),

                        Toggle::make('is_eco_friendly')
                            ->label('♻️ Экологичный')
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
                    ->icon('heroicon-m-heart')
                    ->limit(35),

                BadgeColumn::make('sport_type')
                    ->label('Вид спорта')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'basketball' => '🏀 Баскетбол',
                        'tennis' => '🎾 Теннис',
                        'volleyball' => '🏐 Волейбол',
                        'swimming' => '🏊 Плавание',
                        'running' => '🏃 Бег',
                        'cycling' => '🚴 Велоспорт',
                        'fitness' => '💪 Фитнес',
                        default => 'Прочее',
                    })
                    ->color(fn ($state) => match($state) {
                        'basketball' => 'orange',
                        'tennis' => 'green',
                        'volleyball' => 'yellow',
                        'swimming' => 'cyan',
                        'running' => 'red',
                        'cycling' => 'purple',
                        'fitness' => 'pink',
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
                    ->alignment('center'),

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

                BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемый')
                    ->toggleable(),

                BooleanColumn::make('is_bestseller')
                    ->label('🏆 Бестселлер')
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
                SelectFilter::make('sport_type')
                    ->label('Вид спорта')
                    ->options([
                        'football' => 'Футбол',
                        'basketball' => 'Баскетбол',
                        'tennis' => 'Теннис',
                        'volleyball' => 'Волейбол',
                        'swimming' => 'Плавание',
                        'running' => 'Бег',
                        'cycling' => 'Велоспорт',
                        'fitness' => 'Фитнес',
                    ])
                    ->multiple(),

                SelectFilter::make('equipment_type')
                    ->label('Тип')
                    ->options([
                        'ball' => 'Мяч',
                        'racket' => 'Ракетка',
                        'protective' => 'Защита',
                        'apparel' => 'Одежда',
                        'shoes' => 'Обувь',
                    ])
                    ->multiple(),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                TernaryFilter::make('is_bestseller')
                    ->label('Бестселлер'),

                Filter::make('price_budget')
                    ->label('До 5000 ₽')
                    ->query(fn (Builder $query) => $query->where('price', '<', 500000)),

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
                            $this->logger->info('Sport equipment featured', ['equipment_id' => $record->id, 'correlation_id' => $record->correlation_id]);
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
                                $this->logger->info('Sport equipment bulk activated', ['equipment_id' => $record->id, 'correlation_id' => $record->correlation_id]);
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
                'index' => \App\Filament\Tenant\Resources\Sports\Pages\ListEquipment::route('/'),
                'create' => \App\Filament\Tenant\Resources\Sports\Pages\CreateEquipment::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Sports\Pages\EditEquipment::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}
