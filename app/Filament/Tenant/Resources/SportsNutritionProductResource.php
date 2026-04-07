<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class SportsNutritionProductResource extends Resource
{

    protected static ?string $model = SportsNutritionProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-beaker';
        protected static ?string $navigationGroup = 'Health & Fitness';
        protected static ?string $navigationLabel = 'Supplement Catalog';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General supplement Info')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('store_id')
                                ->relationship('store', 'name')
                                ->label('Boutique Source')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->hint('Product marketing name.'),
                            Forms\Components\TextInput::make('sku')
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(50)
                                ->hint('The unique SKU (must be immutable after save).'),
                            Forms\Components\TextInput::make('brand')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g. Optimum Nutrition, MyProtein'),
                            Forms\Components\Select::make('form_factor')
                                ->options([
                                    'powder' => 'Powder (Scoop based)',
                                    'capsules' => 'Capsules/Tablets',
                                    'liquid' => 'Liquid/Shot',
                                    'bar' => 'Protein Bar',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(1000)
                                ->columnSpan(3),
                        ]),

                    Forms\Components\Section::make('Commercial & Mode-Based Pricing')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('price_b2c')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks (RUB × 100)')
                                ->hint('Base Retail Price.'),
                            Forms\Components\TextInput::make('price_b2b')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks (approx. wholesale)')
                                ->hint('Price for partners/coaches.'),
                            Forms\Components\TextInput::make('stock_quantity')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->hint('Current real inventory count.'),
                            Forms\Components\DatePicker::make('expiry_date')
                                ->label('Stock Expiry Date')
                                ->required()
                                ->hint('DO NOT STOCK near-expiry supplements (< 30d).'),
                            Forms\Components\TextInput::make('servings_count')
                                ->numeric()
                                ->default(30)
                                ->required()
                                ->hint('Number of servings per container.'),
                        ]),

                    Forms\Components\Section::make('AI Nutrition Facts & Safety')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Fieldset::make('Macros (per serving)')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nutrition_facts.calories')
                                        ->numeric()
                                        ->label('Energy (kCal)')
                                        ->required(),
                                    Forms\Components\TextInput::make('nutrition_facts.protein')
                                        ->numeric()
                                        ->label('Protein (g)')
                                        ->required(),
                                    Forms\Components\TextInput::make('nutrition_facts.carbs')
                                        ->numeric()
                                        ->label('Carbohydrates (g)')
                                        ->required(),
                                    Forms\Components\TextInput::make('nutrition_facts.fat')
                                        ->numeric()
                                        ->label('Total Fat (g)')
                                        ->required(),
                                ]),
                            Forms\Components\Fieldset::make('Safety & Dietary Labels')
                                ->schema([
                                    Forms\Components\CheckboxList::make('allergens')
                                        ->options([
                                            'milk' => 'Dairy (Whey/Casein)',
                                            'soy' => 'Soy Protein',
                                            'nuts' => 'Peanuts/Tree Nuts',
                                            'gluten' => 'Gluten/Wheat',
                                            'eggs' => 'Eggs',
                                        ])
                                        ->columns(2)
                                        ->required(),
                                    Forms\Components\Toggle::make('is_vegan')
                                        ->label('Vegan Certified')
                                        ->required(),
                                    Forms\Components\Toggle::make('is_gmo_free')
                                        ->label('GMO Free')
                                        ->required(),
                                    Forms\Components\Toggle::make('is_published')
                                        ->label('Active Catalog Item')
                                        ->required(),
                                ]),
                        ]),

                    Forms\Components\Section::make('Search & Correlation Tags')
                        ->schema([
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('e.g. high-stim, pre-workout, isolate, creapure')
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->hint('Unique security tracking CID.')
                                ->columnSpan(2),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListSportsNutritionProduct::route('/'),
                'create' => Pages\CreateSportsNutritionProduct::route('/create'),
                'edit' => Pages\EditSportsNutritionProduct::route('/{record}/edit'),
                'view' => Pages\ViewSportsNutritionProduct::route('/{record}'),
            ];
        }
}
