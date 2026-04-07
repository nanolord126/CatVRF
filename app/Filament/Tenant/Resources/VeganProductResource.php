<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class VeganProductResource extends Resource
{

    protected static ?string $model = VeganProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Vegan Products Marketplace';
        protected static ?string $label = 'Plant-Based Product';

        /**
         * Define visual form for creating and editing vegan products.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Primary details for the plant-based product.')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (string $state, Forms\Set $set) => $set('slug', Str::slug($state))),

                            Forms\Components\TextInput::make('slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),

                            Forms\Components\Select::make('vegan_store_id')
                                ->label('Store')
                                ->relationship('store', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('vegan_category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])->columns(2),

                    Forms\Components\Section::make('Pricing & Inventory')
                        ->description('Financial and stock control.')
                        ->schema([
                            Forms\Components\TextInput::make('price_b2c')
                                ->label('B2C Price (Kopecks)')
                                ->numeric()
                                ->required()
                                ->suffix('коп.'),

                            Forms\Components\TextInput::make('price_b2b')
                                ->label('B2B Price (Kopecks)')
                                ->numeric()
                                ->required(),

                            Forms\Components\TextInput::make('stock_quantity')
                                ->label('Initial Stock')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->unique(ignoreRecord: true),
                        ])->columns(2),

                    Forms\Components\Section::make('Health & Nutrition (JSONB)')
                        ->description('Detailed nutritional data and allergen information.')
                        ->schema([
                            Forms\Components\KeyValue::make('nutrition_info')
                                ->label('Nutrition per 100g')
                                ->addable()
                                ->deletable()
                                ->keyLabel('Metric (e.g., protein)')
                                ->valueLabel('Value'),

                            Forms\Components\CheckboxList::make('allergen_info')
                                ->label('Contains Allergens')
                                ->options([
                                    'nuts' => 'Nuts',
                                    'soy' => 'Soy',
                                    'gluten' => 'Gluten',
                                    'sesame' => 'Sesame',
                                    'mustard' => 'Mustard',
                                    'celery' => 'Celery',
                                ])->columns(3),
                        ]),

                    Forms\Components\Section::make('Marketing')
                        ->schema([
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('New Tag'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Visible on Marketplace')
                                ->default(true),

                            Forms\Components\TextInput::make('correlation_id')
                                ->label('Correlation ID (Read-only)')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(fn () => (string) Str::uuid()),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListVeganProduct::route('/'),
                'create' => Pages\CreateVeganProduct::route('/create'),
                'edit' => Pages\EditVeganProduct::route('/{record}/edit'),
                'view' => Pages\ViewVeganProduct::route('/{record}'),
            ];
        }
}
