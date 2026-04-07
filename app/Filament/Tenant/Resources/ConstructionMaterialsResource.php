<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class ConstructionMaterialsResource extends Resource
{

    protected static ?string $model = ConstructionMaterial::class;

        protected static ?string $navigationIcon = 'heroicon-o-collection';

        protected static ?string $navigationGroup = 'Resources';

        protected static ?int $navigationSort = 0;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Hidden::make('tenant_id')
                        ->default(fn () => filament()->getTenant()->id ?? null),

                    Hidden::make('correlation_id')
                        ->default(fn () => Str::uuid()->toString()),

                    Section::make('Основная информация')
                        ->description('Базовые сведения о материале')
                        ->icon('heroicon-m-information-circle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Название материала')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->placeholder('Например: Кирпич красный облицовочный'),

                            TextInput::make('sku')
                                ->label('Артикул (SKU)')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->columnSpan(1),

                            TextInput::make('slug')
                                ->label('Идентификатор URL')
                                ->unique(ignoreRecord: true)
                                ->columnSpan(1),

                            Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'draft' => 'Черновик',
                                    'published' => 'Опубликовано',
                                    'archived' => 'Архив',
                                ])
                                ->default('draft')
                                ->columnSpan(1),

                            Select::make('category')
                                ->label('Категория')
                                ->options([
                                    'brick' => 'Кирпич и камень',
                                    'cement' => 'Цемент и вяжущие',
                                    'wood' => 'Материалы из дерева',
                                    'metal' => 'Металлические изделия',
                                    'insulation' => 'Утеплители',
                                    'roofing' => 'Кровельные материалы',
                                ])
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('brand')
                                ->label('Производитель')
                                ->maxLength(100)
                                ->columnSpan(1),
                        ]),

                    Section::make('Характеристики')
                        ->description('Технические параметры')
                        ->icon('heroicon-m-beaker')
                        ->columns(3)
                        ->schema([
                            TextInput::make('unit')
                                ->label('Единица измерения')
                                ->required()
                                ->maxLength(20)
                                ->columnSpan(1)
                                ->placeholder('м, м², м³, шт.'),

                            TextInput::make('weight_kg')
                                ->label('Вес (кг)')
                                ->numeric()
                                ->columnSpan(1),

                            TextInput::make('dimensions')
                                ->label('Габариты (ЛхШхВ)')
                                ->maxLength(100)
                                ->columnSpan(1),

                            TextInput::make('price_per_unit')
                                ->label('Цена за единицу (₽)')
                                ->numeric()
                                ->required()
                                ->columnSpan(1),

                            TextInput::make('minimum_order')
                                ->label('Минимальный заказ')
                                ->numeric()
                                ->columnSpan(1),

                            TextInput::make('stock_quantity')
                                ->label('Остаток на складе')
                                ->numeric()
                                ->required()
                                ->columnSpan(1),
                        ]),

                    Section::make('Описание и контент')
                        ->description('Подробная информация о товаре')
                        ->icon('heroicon-m-document-text')
                        ->columns(1)
                        ->schema([
                            Textarea::make('short_description')
                                ->label('Краткое описание')
                                ->maxLength(500)
                                ->rows(3)
                                ->placeholder('Краткое описание для списков товаров'),

                            RichEditor::make('description')
                                ->label('Полное описание')
                                ->required()
                                ->columnSpan('full')
                                ->maxLength(5000),

                            Textarea::make('specifications')
                                ->label('Спецификации и стандарты')
                                ->maxLength(2000)
                                ->rows(3)
                                ->columnSpan('full'),
                        ]),

                    Section::make('Визуальное представление')
                        ->description('Медиа-контент')
                        ->icon('heroicon-m-photo')
                        ->collapsed()
                        ->schema([
                            FileUpload::make('image')
                                ->label('Основное изображение')
                                ->image()
                                ->directory('construction-materials')
                                ->columnSpan(1),

                            FileUpload::make('gallery')
                                ->label('Галерея')
                                ->multiple()
                                ->image()
                                ->directory('construction-materials/gallery')
                                ->columnSpan(2),

                            FileUpload::make('pdf_specification')
                                ->label('PDF технические условия')
                                ->acceptedFileTypes(['application/pdf'])
                                ->directory('construction-materials/docs')
                                ->columnSpan(1),
                        ]),

                    Section::make('SEO и маркетинг')
                        ->description('Поисковая оптимизация')
                        ->icon('heroicon-m-megaphone')
                        ->collapsed()
                        ->columns(2)
                        ->schema([
                            TextInput::make('meta_title')
                                ->label('Meta Title')
                                ->maxLength(60)
                                ->columnSpan(2),

                            Textarea::make('meta_description')
                                ->label('Meta Description')
                                ->maxLength(160)
                                ->rows(2)
                                ->columnSpan(2),

                            TagsInput::make('keywords')
                                ->label('Ключевые слова')
                                ->columnSpan(2),

                            TextInput::make('seo_focus_keyword')
                                ->label('Основной ключ. слово')
                                ->maxLength(100)
                                ->columnSpan(1),

                            TextInput::make('page_title')
                                ->label('Заголовок страницы H1')
                                ->maxLength(100)
                                ->columnSpan(1),
                        ]),

                    Section::make('Классификация')
                        ->description('Тегирование и категоризация')
                        ->icon('heroicon-m-tag')
                        ->collapsed()
                        ->columns(2)
                        ->schema([
                            TagsInput::make('tags')
                                ->label('Теги')
                                ->columnSpan(2),

                            Select::make('material_type')
                                ->label('Тип материала')
                                ->options([
                                    'natural' => 'Натуральные',
                                    'composite' => 'Композитные',
                                    'synthetic' => 'Синтетические',
                                    'recycled' => 'Вторичные материалы',
                                ])
                                ->multiple()
                                ->columnSpan(1),

                            Toggle::make('is_eco_friendly')
                                ->label('Экологичный материал')
                                ->columnSpan(1),
                        ]),

                    Section::make('Управление состоянием')
                        ->description('Видимость и активность')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->collapsed()
                        ->columns(3)
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Активен')
                                ->default(true),

                            Toggle::make('is_featured')
                                ->label('Избранный')
                                ->default(false),

                            Toggle::make('in_stock')
                                ->label('В наличии')
                                ->default(true),

                            DatePicker::make('published_at')
                                ->label('Дата публикации')
                                ->columnSpan(2),

                            TextInput::make('priority')
                                ->label('Приоритет')
                                ->numeric()
                                ->default(0),
                        ]),

                    Section::make('Дополнительно')
                        ->description('Расширенные параметры')
                        ->icon('heroicon-m-adjustments-horizontal')
                        ->collapsed()
                        ->columns(2)
                        ->schema([
                            Textarea::make('notes')
                                ->label('Внутренние заметки')
                                ->rows(3)
                                ->columnSpan(2)
                                ->hint('Видны только модераторам'),

                            DateTimePicker::make('created_at')
                                ->label('Создано')
                                ->disabled()
                                ->columnSpan(1),

                            DateTimePicker::make('updated_at')
                                ->label('Обновлено')
                                ->disabled()
                                ->columnSpan(1),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListConstructionMaterials::route('/'),
                'create' => Pages\CreateConstructionMaterials::route('/create'),
                'edit' => Pages\EditConstructionMaterials::route('/{record}/edit'),
                'view' => Pages\ViewConstructionMaterials::route('/{record}'),
            ];
        }
}
