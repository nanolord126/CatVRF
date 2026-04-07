<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class BeverageItemResource extends Resource
{

    protected static ?string $model = BeverageItem::class;

        protected static ?string $navigationIcon = 'heroicon-o-beaker';

        protected static ?string $navigationGroup = 'Beverages Vertical';

        protected static ?string $modelLabel = 'Drink / Beverage';

        /**
         * Complete form definition (>= 60 lines per canon 2026).
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Drink Details')
                        ->description('Configuration of the specific beverage item')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->label('Drink Name')
                                ->placeholder('e.g. Lavender Latte'),

                            Forms\Components\Select::make('shop_id')
                                ->relationship('shop', 'name')
                                ->required()
                                ->label('Destination Shop')
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name', function (Builder $query, Forms\Get $get) {
                                    return $query->where('shop_id', $get('shop_id'));
                                })
                                ->required()
                                ->label('Menu Category')
                                ->placeholder('Select shop first')
                                ->searchable()
                                ->preload(),

                            Forms\Components\Textarea::make('description')
                                ->maxLength(1000)
                                ->label('Product Description')
                                ->columnSpanFull(),
                        ])->columns(3),

                    Forms\Components\Section::make('Pricing & Inventory')
                        ->description('Financial and stock control')
                        ->schema([
                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix('RUB (cents)')
                                ->label('Price in Cents')
                                ->helperText('e.g. 29900 for 299 RUB'),

                            Forms\Components\TextInput::make('volume_ml')
                                ->required()
                                ->numeric()
                                ->suffix('ml')
                                ->label('Volume'),

                            Forms\Components\TextInput::make('stock_count')
                                ->numeric()
                                ->default(0)
                                ->label('Current Stock')
                                ->helperText('Mainly for bottled beverages'),

                            Forms\Components\Toggle::make('is_available')
                                ->default(true)
                                ->label('Available for order'),
                        ])->columns(4),

                    Forms\Components\Section::make('Health & Quality')
                        ->description('Composition, allergens and freshness')
                        ->schema([
                            Forms\Components\TagsInput::make('ingredients')
                                ->label('Beverage Ingredients')
                                ->placeholder('e.g. arabica, milk, syrup'),

                            Forms\Components\TagsInput::make('allergens')
                                ->label('Mandatory Allergens Info')
                                ->placeholder('e.g. lactose, nuts, gluten'),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('nutritional_value.calories')->label('Calories')->numeric(),
                                    Forms\Components\TextInput::make('nutritional_value.protein')->label('Protein')->numeric(),
                                    Forms\Components\TextInput::make('nutritional_value.carbs')->label('Carbs')->numeric(),
                                ]),

                            Forms\Components\Select::make('freshness_control_type')
                                ->options([
                                    'none' => 'No Control',
                                    'hourly' => 'Hourly Check',
                                    'daily' => 'Daily Rotation',
                                    'expiration' => 'Hard Expiration date',
                                ])
                                ->label('Quality Control Strategy'),

                            Forms\Components\TextInput::make('shelf_life_hours')
                                ->numeric()
                                ->suffix('hours')
                                ->label('Shelf Life (After Preparation)'),
                        ])->columns(2),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBeverageItem::route('/'),
                'create' => Pages\CreateBeverageItem::route('/create'),
                'edit' => Pages\EditBeverageItem::route('/{record}/edit'),
                'view' => Pages\ViewBeverageItem::route('/{record}'),
            ];
        }
}
