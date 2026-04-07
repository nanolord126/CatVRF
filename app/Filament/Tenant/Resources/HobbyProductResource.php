<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class HobbyProductResource extends Resource
{

    protected static ?string $model = HobbyProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-scissors';
        protected static ?string $navigationGroup = 'Hobby & Craft';

        /**
         * Define the data entry form for Hobby Products.
         */
        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Core Material Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Product Title')
                            ->placeholder('e.g. Professional Oil Paint Set'),

                        Forms\Components\TextInput::make('sku')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('SKU')
                            ->placeholder('HC-PAINT-001'),

                        Forms\Components\Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()
                            ->label('Source Store')
                            ->searchable(),

                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('DIY Category')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('price_b2c')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('B2C Price (in kopecks)')
                            ->helperText('Retail price per unit in kopecks (e.g. 1000 = 10.00 RUB)'),

                        Forms\Components\TextInput::make('price_b2b')
                            ->numeric()
                            ->prefix('₽')
                            ->label('B2B Wholesale Price')
                            ->helperText('Wholesale price for volume orders (>5 units)'),

                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->label('Current Stock')
                            ->minValue(0),

                        Forms\Components\Select::make('skill_level')
                            ->options([
                                'beginner' => 'Beginner (Safe/No experience)',
                                'intermediate' => 'Intermediate (Hand tools required)',
                                'advanced' => 'Advanced (Heavy equipment/Pro)'
                            ])
                            ->required()
                            ->label('Target Difficulty'),
                    ])->columns(2),

                Forms\Components\Section::make('Media & Metadata')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->image()
                            ->label('Product Showcase Photos')
                            ->directory('hobby/products'),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Craft Tags (e.g. Woodworking, Painting, Sewing)')
                            ->placeholder('Add relevant DIY tags'),

                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->label('Technical Description/Usage Guide')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Enable for Marketplace Sales'),
                    ])->columns(1),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListHobbyProduct::route('/'),
                'create' => Pages\CreateHobbyProduct::route('/create'),
                'edit' => Pages\EditHobbyProduct::route('/{record}/edit'),
                'view' => Pages\ViewHobbyProduct::route('/{record}'),
            ];
        }
}
