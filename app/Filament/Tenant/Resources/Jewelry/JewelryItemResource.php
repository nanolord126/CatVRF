<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry;


use Psr\Log\LoggerInterface;
    use Filament\Resources\Resource;
    use Filament\Forms\Form;
    use Filament\Forms;
    use App\Models\JewelryItem;
    use Filament\Forms\Components\Section;
    use Filament\Forms\Components\TextInput;
    use Filament\Forms\Components\Textarea;
    use Filament\Forms\Components\FileUpload;
    use Filament\Forms\Components\Select;
    use Filament\Forms\Components\TagsInput;
    use Filament\Forms\Components\Toggle;
    use Filament\Forms\Components\Hidden;
    use Filament\Tables\Columns\NumericColumn;
    use Filament\Tables\Actions\BulkActionGroup;
    use Filament\Tables\Actions\DeleteBulkAction;
    use Filament\Tables;
    use Filament\Tables\Columns\{BadgeColumn, IconColumn, ImageColumn, TextColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{Action, ActionGroup, RestoreAction, DeleteAction, EditAction, ViewAction};
    use Filament\Tables\Enums\ActionsPosition;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class JewelryItemResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = JewelryItem::class;

        protected static ?string $navigationIcon = 'heroicon-m-sparkles';
        protected static ?string $navigationGroup = 'Вертикали';
        protected static ?int $navigationSort = 13;
        protected static ?string $label = 'Ювелирное изделие';
        protected static ?string $pluralLabel = 'Ювелирные изделия';

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Section::make('💎 Основная информация')
                    ->description('Базовые сведения о украшении')
                    ->icon('heroicon-m-sparkles')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('sku')
                            ->label('SKU/Артикул')
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
                        Select::make('jewelry_type')
                            ->label('Тип украшения')
                            ->options([
                                'ring' => 'Кольцо',
                                'necklace' => 'Ожерелье',
                                'bracelet' => 'Браслет',
                                'earring' => 'Серьги',
                                'pendant' => 'Кулон',
                                'brooch' => 'Брошь',
                                'anklet' => 'Браслет на ногу',
                                'tiara' => 'Тиара',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ]),
                Section::make('🏆 Материалы и камни')
                    ->description('Драгоценные металлы и камни')
                    ->icon('heroicon-m-gem')
                    ->columns(2)
                    ->schema([
                        Select::make('metal_type')
                            ->label('Тип металла')
                            ->options([
                                'gold' => 'Золото',
                                'silver' => 'Серебро',
                                'platinum' => 'Платина',
                                'white_gold' => 'Белое золото',
                                'rose_gold' => 'Розовое золото',
                                'palladium' => 'Палладий',
                            ])
                            ->required(),
                        Select::make('purity')
                            ->label('Проба')
                            ->options([
                                '375' => '375 (9K)',
                                '585' => '585 (14K)',
                                '750' => '750 (18K)',
                                '950' => '950 (Платина)',
                                '999' => '999 (Чистое)',
                            ])
                            ->default('750'),
                        TextInput::make('weight_grams')
                            ->label('Вес (г)')
                            ->numeric()
                            ->step(0.01),
                        TagsInput::make('stone_types')
                            ->label('Типы камней')
                            ->separator(',')
                            ->suggestions(['Алмаз', 'Рубин', 'Сапфир', 'Изумруд', 'Аметист']),
                    ]),
                Section::make('💰 Цена и финансы')
                    ->description('Ценообразование и комиссии')
                    ->icon('heroicon-m-banknote')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_kopecks')
                            ->label('Цена (руб)')
                            ->numeric()
                            ->required()
                            ->hint('В копейках (× 100)'),
                        TextInput::make('cost_kopecks')
                            ->label('Себестоимость (руб)')
                            ->numeric(),
                        TextInput::make('markup_percent')
                            ->label('Наценка (%)')
                            ->numeric()
                            ->default(30),
                        TextInput::make('insurance_percent')
                            ->label('Страховка (%)')
                            ->numeric()
                            ->default(0.5),
                    ]),
                Section::make('📦 Запасы и управление')
                    ->description('Остатки и резерви')
                    ->icon('heroicon-m-inbox-stack')
                    ->columns(2)
                    ->schema([
                        TextInput::make('current_stock')
                            ->label('В наличии')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TextInput::make('min_stock')
                            ->label('Минимум')
                            ->numeric()
                            ->default(1),
                        Toggle::make('is_made_to_order')
                            ->label('Под заказ')
                            ->default(false),
                        TextInput::make('production_days')
                            ->label('Дней на производство')
                            ->numeric()
                            ->visible(fn (Toggle $toggle) => $toggle->getState()),
                    ]),
                Section::make('⭐ Рейтинг и качество')
                    ->description('Оценки и сертификация')
                    ->icon('heroicon-m-star')
                    ->columns(2)
                    ->schema([
                        TextInput::make('average_rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        TextInput::make('review_count')
                            ->label('Отзывы')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        Toggle::make('has_certificate')
                            ->label('Есть сертификат')
                            ->default(false),
                        TextInput::make('certificate_number')
                            ->label('Номер сертификата')
                            ->maxLength(100)
                            ->visible(fn (Toggle $toggle) => $toggle->getState()),
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
                    TextColumn::make('name')
                        ->label('Название')
                        ->searchable()
                        ->sortable()
                        ->limit(22),
                    BadgeColumn::make('jewelry_type')
                        ->label('Тип')
                        ->colors([
                            'ring' => 'info',
                            'necklace' => 'success',
                            'bracelet' => 'warning',
                            'earring' => 'danger',
                            'pendant' => 'primary',
                        ])
                        ->sortable(),
                    BadgeColumn::make('metal_type')
                        ->label('Металл')
                        ->colors([
                            'gold' => 'warning',
                            'silver' => 'info',
                            'platinum' => 'secondary',
                            'white_gold' => 'primary',
                            'rose_gold' => 'danger',
                        ]),
                    TextColumn::make('purity')
                        ->label('Проба')
                        ->sortable(),
                    NumericColumn::make('weight_grams')
                        ->label('Вес (г)')
                        ->suffix(' г')
                        ->sortable()
                        ->toggleable(),
                    TextColumn::make('price_kopecks')
                        ->label('Цена')
                        ->money('RUB', locale: 'ru_RU')
                        ->divideBy(100)
                        ->sortable(),
                    NumericColumn::make('current_stock')
                        ->label('Запас')
                        ->sortable(),
                    NumericColumn::make('average_rating')
                        ->label('Рейтинг')
                        ->sortable()
                        ->toggleable(),
                    NumericColumn::make('review_count')
                        ->label('Отзывы')
                        ->sortable()
                        ->toggleable(),
                    IconColumn::make('has_certificate')
                        ->label('Сертификат')
                        ->boolean()
                        ->toggleable(),
                    IconColumn::make('is_made_to_order')
                        ->label('Под заказ')
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
                    SelectFilter::make('jewelry_type')
                        ->label('Тип')
                        ->multiple()
                        ->searchable()
                        ->options([
                            'ring' => 'Кольцо',
                            'necklace' => 'Ожерелье',
                            'bracelet' => 'Браслет',
                            'earring' => 'Серьги',
                            'pendant' => 'Кулон',
                        ]),
                    SelectFilter::make('metal_type')
                        ->label('Металл')
                        ->multiple()
                        ->options([
                            'gold' => 'Золото',
                            'silver' => 'Серебро',
                            'platinum' => 'Платина',
                            'white_gold' => 'Белое золото',
                        ]),
                    SelectFilter::make('purity')
                        ->label('Проба')
                        ->multiple()
                        ->options([
                            '585' => '585 (14K)',
                            '750' => '750 (18K)',
                            '950' => '950 (Платина)',
                        ]),
                    TernaryFilter::make('has_certificate')
                        ->label('Сертифицированные'),
                    TernaryFilter::make('is_made_to_order')
                        ->label('Под заказ'),
                    TrashedFilter::make(),
                ])
                ->actions([
                    ActionGroup::make([
                        ViewAction::make(),
                        EditAction::make(),
                        DeleteAction::make()
                            ->after(function (JewelryItem $record) {
                                $this->logger->info('Jewelry item deleted', [
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
                                $this->logger->info('Jewelry items bulk deleted', [
                                    'correlation_id' => Str::uuid(),
                                ]);
                            }),
                    ])->deselectRecordsAfterCompletion(),
                ]);
        }
}
