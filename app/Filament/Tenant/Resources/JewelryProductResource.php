<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class JewelryProductResource extends Resource
{

    protected static ?string $model = JewelryProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-sparkles';
        protected static ?string $navigationGroup = 'Jewelry Management';
        protected static ?string $navigationLabel = 'Boutique Inventory';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('store_id')
                                ->relationship('store', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('category_id')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('collection_id')
                                ->relationship('collection', 'name')
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
                                ->hint('Auto-generated or manual SKU.'),
                            Forms\Components\Textarea::make('description')
                                ->maxLength(1000)
                                ->columnSpan(3),
                        ]),

                    Forms\Components\Section::make('Commercial & Stock')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('price_b2c')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks')
                                ->hint('Base Retail Price (100 = 1 RUB).'),
                            Forms\Components\TextInput::make('price_b2b')
                                ->numeric()
                                ->required()
                                ->suffix('Kopecks')
                                ->hint('Wholesale B2B Price for Partners.'),
                            Forms\Components\TextInput::make('stock_quantity')
                                ->numeric()
                                ->required()
                                ->default(0),
                        ]),

                    Forms\Components\Section::make('Technical Details (Metal & Stones)')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('metal_type')
                                ->options([
                                    'yellow-gold' => 'Yellow Gold',
                                    'white-gold' => 'White Gold',
                                    'rose-gold' => 'Rose Gold',
                                    'platinum' => 'Platinum',
                                    'silver' => 'Silver',
                                    'palladium' => 'Palladium',
                                ])
                                ->required(),
                            Forms\Components\Select::make('metal_fineness')
                                ->options([
                                    '585' => '585 (Gold)',
                                    '750' => '750 (Gold)',
                                    '925' => '925 (Silver)',
                                    '950' => '950 (Platinum)',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('weight_grams')
                                ->numeric()
                                ->required()
                                ->step(0.01),
                            Forms\Components\Repeater::make('gemstones')
                                ->columnSpan(3)
                                ->schema([
                                    Forms\Components\TextInput::make('stone')
                                        ->required()
                                        ->placeholder('Diamond, Ruby, Emerald'),
                                    Forms\Components\TextInput::make('carat')
                                        ->numeric()
                                        ->required()
                                        ->placeholder('e.g. 0.5'),
                                    Forms\Components\TextInput::make('clarity')
                                        ->placeholder('VVS1, VS2, etc.'),
                                ]),
                        ]),

                    Forms\Components\Section::make('Security, Packing & AI Features')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Fieldset::make('Certification')
                                ->schema([
                                    Forms\Components\Toggle::make('has_certification')
                                        ->label('Diamond/Stone Certificate')
                                        ->required()
                                        ->reactive(),
                                    Forms\Components\TextInput::make('certificate_number')
                                        ->visible(fn ($get) => $get('has_certification'))
                                        ->required(fn ($get) => $get('has_certification'))
                                        ->maxLength(100),
                                ]),
                            Forms\Components\Fieldset::make('Options')
                                ->schema([
                                    Forms\Components\Toggle::make('is_customizable')
                                        ->label('Allow Engraving/Customization')
                                        ->required(),
                                    Forms\Components\Toggle::make('is_gift_wrapped')
                                        ->label('Premium Gift Wrapping Included')
                                        ->required(),
                                    Forms\Components\Toggle::make('is_published')
                                        ->label('Active for Clients')
                                        ->required(),
                                ]),
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('Style (Luxury, Minimalist), Occasion, etc.')
                                ->columnSpan(2),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListJewelryProduct::route('/'),
                'create' => Pages\CreateJewelryProduct::route('/create'),
                'edit' => Pages\EditJewelryProduct::route('/{record}/edit'),
                'view' => Pages\ViewJewelryProduct::route('/{record}'),
            ];
        }
}
