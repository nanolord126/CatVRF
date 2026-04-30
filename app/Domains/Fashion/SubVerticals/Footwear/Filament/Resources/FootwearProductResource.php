<?php

declare(strict_types=1);

namespace App\Domains\Footwear\Filament\Resources;

use App\Domains\Footwear\Models\FootwearProduct;
use App\Domains\Footwear\Models\FootwearVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class FootwearProductResource extends Resource
{
    protected static ?string $model = FootwearProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-shoe-print';

    protected static ?string $navigationLabel = 'Обувь';

    protected static ?string $modelLabel = 'Обувь';

    protected static ?string $pluralModelLabel = 'Обувь';

    protected static ?string $navigationGroup = 'Footwear';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('brand')
                            ->label('Бренд')
                            ->required(),
                        Forms\Components\TextInput::make('color')
                            ->label('Цвет')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Материалы и подошва')
                    ->schema([
                        Forms\Components\TextInput::make('material')
                            ->label('Материал')
                            ->required(),
                        Forms\Components\Select::make('sole_type')
                            ->label('Тип подошвы')
                            ->options([
                                'rubber' => 'Резина',
                                'leather' => 'Кожа',
                                'synthetic' => 'Синтетика',
                                'eva' => 'EVA',
                                'pu' => 'PU',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\Select::make('season')
                            ->label('Сезон')
                            ->options([
                                'spring' => 'Весна',
                                'summer' => 'Лето',
                                'autumn' => 'Осень',
                                'winter' => 'Зима',
                                'all_season' => 'Всесезонный',
                            ])
                            ->required(),
                        Forms\Components\Select::make('purpose')
                            ->label('Назначение')
                            ->options([
                                'casual' => 'Повседневная',
                                'sports' => 'Спортивная',
                                'formal' => 'Официальная',
                                'outdoor' => 'Для активного отдыха',
                                'work' => 'Рабочая',
                                'running' => 'Для бега',
                                'walking' => 'Для ходьбы',
                            ])
                            ->required(),
                        Forms\Components\Select::make('gender')
                            ->label('Пол')
                            ->options([
                                'male' => 'Мужской',
                                'female' => 'Женский',
                                'unisex' => 'Унисекс',
                                'kids' => 'Детский',
                            ])
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Уход')
                    ->schema([
                        Forms\Components\Textarea::make('care_instructions')
                            ->label('Инструкции по уходу')
                            ->rows(3),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Цены и склад')
                    ->schema([
                        Forms\Components\TextInput::make('price_b2c')
                            ->label('Цена B2C (коп.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('price_b2b')
                            ->label('Цена B2B (коп.)')
                            ->numeric(),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Количество на складе')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Изображения')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Изображения')
                            ->multiple()
                            ->image()
                            ->directory('footwear/products')
                            ->reorderable()
                            ->helperText('Обязательные ракурсы: 360°, подошва, вид сверху'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Рекомендуемый'),
                        Forms\Components\Toggle::make('is_new')
                            ->label('Новинка'),
                        Forms\Components\Toggle::make('is_discounted')
                            ->label('Со скидкой'),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'inactive' => 'Неактивен',
                                'draft' => 'Черновик',
                                'out_of_stock' => 'Нет в наличии',
                                'discontinued' => 'Снят с производства',
                            ])
                            ->default('active'),
                        Forms\Components\TextInput::make('weight_grams')
                            ->label('Вес (г)')
                            ->numeric(),
                        Forms\Components\TextInput::make('warranty_months')
                            ->label('Гарантия (мес.)')
                            ->numeric(),
                        Forms\Components\TextInput::make('return_policy_days')
                            ->label('Возврат (дней)')
                            ->numeric(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Варианты (Размеры и ширина)')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->label('Варианты')
                            ->relationship('variants')
                            ->schema([
                                Forms\Components\TextInput::make('sku_variant')
                                    ->label('SKU варианта')
                                    ->required(),
                                Forms\Components\TextInput::make('color')
                                    ->label('Цвет')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color_code')
                                    ->label('Цвет (HEX)'),
                                Forms\Components\TextInput::make('size_eu')
                                    ->label('Размер EU')
                                    ->required(),
                                Forms\Components\TextInput::make('size_us')
                                    ->label('Размер US')
                                    ->required(),
                                Forms\Components\TextInput::make('size_uk')
                                    ->label('Размер UK')
                                    ->required(),
                                Forms\Components\TextInput::make('size_cm')
                                    ->label('Размер (см)')
                                    ->numeric()
                                    ->required(),
                                Forms\Components\Select::make('width')
                                    ->label('Ширина')
                                    ->options([
                                        'narrow' => 'Узкая',
                                        'regular' => 'Обычная',
                                        'wide' => 'Широкая',
                                        'extra_wide' => 'Очень широкая',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('price_adjustment')
                                    ->label('Корректировка цены')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('current_stock')
                                    ->label('Количество')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Активен')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_default')
                                    ->label('По умолчанию'),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['color'] . ' - EU ' . $state['size_eu'] . ' (' . $state['width'] . ')' ?? null),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('SEO Заголовок')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('seo_description')
                            ->label('SEO Описание')
                            ->rows(2),
                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images.0')
                    ->label('Изображение')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Бренд')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('color')
                    ->label('Цвет')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_b2c')
                    ->label('Цена B2C')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Склад')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Рекоменд.')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_new')
                    ->label('Новинка')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_discounted')
                    ->label('Скидка')
                    ->boolean(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                        'draft' => 'Черновик',
                        'out_of_stock' => 'Нет в наличии',
                        'discontinued' => 'Снят с производства',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('season')
                    ->label('Сезон')
                    ->options([
                        'spring' => 'Весна',
                        'summer' => 'Лето',
                        'autumn' => 'Осень',
                        'winter' => 'Зима',
                        'all_season' => 'Всесезонный',
                    ]),
                Tables\Filters\SelectFilter::make('purpose')
                    ->label('Назначение')
                    ->options([
                        'casual' => 'Повседневная',
                        'sports' => 'Спортивная',
                        'formal' => 'Официальная',
                        'outdoor' => 'Для активного отдыха',
                        'work' => 'Рабочая',
                        'running' => 'Для бега',
                        'walking' => 'Для ходьбы',
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Пол')
                    ->options([
                        'male' => 'Мужской',
                        'female' => 'Женский',
                        'unisex' => 'Унисекс',
                        'kids' => 'Детский',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемые'),
                Tables\Filters\TernaryFilter::make('is_new')
                    ->label('Новинки'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'variants' => FootwearVariant::class,
        ];
    }
}
