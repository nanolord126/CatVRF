<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Collectibles;


use Psr\Log\LoggerInterface;
    use Filament\Forms;
    use Filament\Resources\Resource;
    use Filament\Forms\Form;
    use Filament\Tables;
    use Filament\Tables\Columns\{BadgeColumn, IconColumn, ImageColumn, TextColumn, NumericColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{Action, ActionGroup, RestoreAction, DeleteAction, EditAction, ViewAction};
    use Filament\Tables\Actions\BulkActions\{BulkActionGroup, DeleteBulkAction};
    use Filament\Tables\Enums\ActionsPosition;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class CollectibleResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = Collectible::class;

        protected static ?string $navigationIcon = 'heroicon-m-gift';
        protected static ?string $navigationGroup = 'Вертикали';
        protected static ?int $navigationSort = 11;
        protected static ?string $label = 'Коллекционный предмет';
        protected static ?string $pluralLabel = 'Коллекционные предметы';

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Section::make('🎁 Коллекционный предмет')
                    ->description('Основная информация о предмете')
                    ->icon('heroicon-m-gift')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpan(2),
                        FileUpload::make('image_path')
                            ->label('Основное фото')
                            ->image()
                            ->maxSize(10240)
                            ->columnSpan(1),
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'vintage' => 'Винтаж',
                                'figurines' => 'Фигурки',
                                'coins' => 'Монеты',
                                'stamps' => 'Марки',
                                'cards' => 'Карточки',
                                'books' => 'Книги',
                                'memorabilia' => 'Памятные вещи',
                                'art' => 'Искусство',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ]),
                Section::make('🌍 Происхождение')
                    ->description('История и страна происхождения')
                    ->icon('heroicon-m-globe-alt')
                    ->columns(2)
                    ->schema([
                        Select::make('country_of_origin')
                            ->label('Страна происхождения')
                            ->searchable()
                            ->options([
                                'Russia' => 'Россия',
                                'USA' => 'США',
                                'UK' => 'Великобритания',
                                'France' => 'Франция',
                                'Japan' => 'Япония',
                                'Germany' => 'Германия',
                            ]),
                        TextInput::make('year_produced')
                            ->label('Год производства')
                            ->numeric(),
                        Textarea::make('history')
                            ->label('История предмета')
                            ->rows(2)
                            ->columnSpan(2),
                    ]),
                Section::make('🛠️ Материалы')
                    ->description('Состав и материалы')
                    ->icon('heroicon-m-squares-2x2')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('materials')
                            ->label('Материалы')
                            ->separator(',')
                            ->suggestions(['Дерево', 'Керамика', 'Металл', 'Стекло', 'Пластик', 'Текстиль']),
                        Select::make('condition')
                            ->label('Состояние')
                            ->options([
                                'mint' => 'Идеальное',
                                'near_mint' => 'Отличное',
                                'excellent' => 'Хорошее',
                                'very_good' => 'Очень хорошее',
                                'good' => 'Удовлетворительное',
                                'fair' => 'Среднее',
                            ])
                            ->default('very_good'),
                        TextInput::make('weight_grams')
                            ->label('Вес (г)')
                            ->numeric(),
                        TextInput::make('dimensions')
                            ->label('Размеры (см)')
                            ->placeholder('10 × 15 × 5'),
                    ]),
                Section::make('📜 Сертификация')
                    ->description('Подлинность и документы')
                    ->icon('heroicon-m-document-check')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_authenticated')
                            ->label('Проверена подлинность')
                            ->default(false),
                        TextInput::make('authenticity_number')
                            ->label('Номер сертификата')
                            ->maxLength(100),
                        Select::make('certification_body')
                            ->label('Организация-сертификатор')
                            ->searchable()
                            ->options([
                                'PSA' => 'PSA',
                                'CGC' => 'CGC',
                                'SGC' => 'SGC',
                                'Other' => 'Другое',
                            ]),
                        DatePicker::make('certification_date')
                            ->label('Дата сертификации'),
                    ]),
                Section::make('💰 Цена и финансы')
                    ->description('Ценообразование')
                    ->icon('heroicon-m-banknote')
                    ->columns(2)
                    ->schema([
                        TextInput::make('estimated_value_kopecks')
                            ->label('Оценочная стоимость (руб)')
                            ->numeric()
                            ->required()
                            ->hint('В копейках'),
                        TextInput::make('insurance_value_kopecks')
                            ->label('Страховая стоимость (руб)')
                            ->numeric(),
                        TextInput::make('current_price_kopecks')
                            ->label('Текущая цена продажи (руб)')
                            ->numeric(),
                    ]),
                Section::make('⭐ Рейтинг и популярность')
                    ->description('Отзывы и статистика')
                    ->icon('heroicon-m-star')
                    ->columns(2)
                    ->schema([
                        TextInput::make('average_rating')
                            ->label('Средний рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TextInput::make('review_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TextInput::make('purchase_count')
                            ->label('Раз куплено')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        Toggle::make('is_rare')
                            ->label('Редкий предмет')
                            ->default(false),
                    ]),
                Section::make('🔧 Служебные поля')
                    ->description('Система')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->collapsed()
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')
                            ->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')
                            ->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ]),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table
                ->columns([
                    ImageColumn::make('image_path')
                        ->label('Фото')
                        ->size(50),
                    TextColumn::make('title')
                        ->label('Название')
                        ->searchable()
                        ->sortable()
                        ->limit(20),
                    BadgeColumn::make('category')
                        ->label('Категория')
                        ->colors([
                            'vintage' => 'info',
                            'figurines' => 'success',
                            'coins' => 'warning',
                            'stamps' => 'danger',
                            'cards' => 'info',
                            'books' => 'primary',
                            'memorabilia' => 'secondary',
                        ])
                        ->sortable(),
                    TextColumn::make('year_produced')
                        ->label('Год')
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('estimated_value_kopecks')
                        ->label('Стоимость')
                        ->money('RUB', locale: 'ru_RU')
                        ->divideBy(100)
                        ->sortable(),
                    BadgeColumn::make('condition')
                        ->label('Состояние')
                        ->colors([
                            'mint' => 'success',
                            'near_mint' => 'success',
                            'excellent' => 'info',
                            'very_good' => 'info',
                            'good' => 'warning',
                            'fair' => 'danger',
                        ]),
                    NumericColumn::make('average_rating')
                        ->label('Рейтинг')
                        ->sortable()
                        ->toggleable(),
                    NumericColumn::make('review_count')
                        ->label('Отзывы')
                        ->sortable()
                        ->toggleable(),
                    NumericColumn::make('purchase_count')
                        ->label('Покупок')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    IconColumn::make('is_rare')
                        ->label('Редкий')
                        ->boolean()
                        ->toggleable(),
                    IconColumn::make('is_authenticated')
                        ->label('Аутентифицирован')
                        ->boolean()
                        ->toggleable(),
                    TextColumn::make('sku')
                        ->label('SKU')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime('d.m.Y H:i')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('category')
                        ->label('Категория')
                        ->multiple()
                        ->searchable()
                        ->options([
                            'vintage' => 'Винтаж',
                            'figurines' => 'Фигурки',
                            'coins' => 'Монеты',
                            'stamps' => 'Марки',
                            'cards' => 'Карточки',
                        ]),
                    SelectFilter::make('condition')
                        ->label('Состояние')
                        ->options([
                            'mint' => 'Идеальное',
                            'excellent' => 'Хорошее',
                            'good' => 'Удовлетворительное',
                        ]),
                    SelectFilter::make('country_of_origin')
                        ->label('Страна')
                        ->searchable()
                        ->options([
                            'Russia' => 'Россия',
                            'USA' => 'США',
                            'UK' => 'Великобритания',
                        ]),
                    TernaryFilter::make('is_rare')
                        ->label('Редкие предметы'),
                    TernaryFilter::make('is_authenticated')
                        ->label('Аутентифицированные'),
                    TrashedFilter::make(),
                ])
                ->actions([
                    ActionGroup::make([
                        ViewAction::make(),
                        EditAction::make(),
                        DeleteAction::make()
                            ->after(function (Collectible $record) {
                                $this->logger->info('Collectible deleted', [
                                    'id' => $record->id,
                                    'correlation_id' => $record->correlation_id ?? Str::uuid(),
                                ]);
                            }),
                        RestoreAction::make(),
                    ])->icon('heroicon-m-ellipsis-horizontal'),
                ], position: ActionsPosition::BeforeColumns)
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->action(function () {
                                $this->logger->info('Collectibles bulk deleted', [
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                    ])->deselectRecordsAfterCompletion(),
                ]);
        }
}
