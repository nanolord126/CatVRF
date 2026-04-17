<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FashionProductResource extends Resource
{

    protected static ?string $model = FashionProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-briefcase';

        protected static ?string $navigationGroup = 'Fashion & Style';

        protected static ?string $modelLabel = 'Товар';

        protected static ?string $pluralModelLabel = 'Каталог товаров';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Split::make([
                        Forms\Components\Section::make('Витрина и Описание')
                            ->schema([
                                Forms\Components\Hidden::make('correlation_id')
                                    ->default(fn () => (string) Str::uuid()),

                                Forms\Components\TextInput::make('name')
                                    ->label('Название продукта')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Select::make('store_id')
                                    ->label('Магазин / Продавец')
                                    ->relationship('store', 'name', modifyQueryUsing: fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\RichEditor::make('description')
                                    ->label('Описание товара')
                                    ->required(),

                                Forms\Components\FileUpload::make('images')
                                    ->label('Медиа галлерея')
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->directory('fashion/products')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Section::make('Ценообразование и Склад')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('price_b2c')
                                            ->label('Цена B2C (коп.)')
                                            ->required()
                                            ->numeric()
                                            ->placeholder('1000 = 10 руб')
                                            ->suffix('коп'),

                                        Forms\Components\TextInput::make('price_b2b')
                                            ->label('Цена B2B (коп.)')
                                            ->required()
                                            ->numeric()
                                            ->placeholder('800 = 8 руб')
                                            ->suffix('коп'),

                                        Forms\Components\TextInput::make('old_price')
                                            ->label('Старая цена (коп.)')
                                            ->numeric()
                                            ->disabled()
                                            ->helperText('Защищено каноном: не обновляется, если новая выше'),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Остаток на складе')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->hintColor('success'),

                                        Forms\Components\TextInput::make('reserve_quantity')
                                            ->label('В резерве (20 мин)')
                                            ->numeric()
                                            ->disabled()
                                            ->placeholder('0'),

                                        Forms\Components\TextInput::make('min_stock_threshold')
                                            ->label('Крит. порог')
                                            ->numeric()
                                            ->default(5)
                                            ->helperText('Уведомление при достижении'),
                                    ]),

                                Forms\Components\Select::make('category')
                                    ->label('Категория')
                                    ->options([
                                        'men' => 'Мужское',
                                        'women' => 'Женское',
                                        'kids' => 'Детское',
                                        'accessories' => 'Аксессуары',
                                    ])
                                    ->required(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Доступен для продажи')
                                    ->onColor('success')
                                    ->default(true),
                            ]),
                    ])->columnSpanFull(),

                    Forms\Components\Section::make('Техническая спецификация (JSONB)')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\KeyValue::make('measurements')
                                        ->label('Габариты / Размеры')
                                        ->keyLabel('Параметр (ширина, рост)')
                                        ->valueLabel('Значение'),

                                    Forms\Components\KeyValue::make('tags')
                                        ->label('Meta Tags / AI Аналитика')
                                        ->keyLabel('Тег')
                                        ->valueLabel('Значение'),
                                ]),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFashionProduct::route('/'),
                'create' => Pages\CreateFashionProduct::route('/create'),
                'edit' => Pages\EditFashionProduct::route('/{record}/edit'),
                'view' => Pages\ViewFashionProduct::route('/{record}'),
            ];
        }
}
