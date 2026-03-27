<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Luxury\Jewelry\Models\JewelryProduct;
use App\Domains\Luxury\Jewelry\Models\JewelryStore;
use App\Domains\Luxury\Jewelry\Models\JewelryCategory;
use App\Domains\Luxury\Jewelry\Models\JewelryCollection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * JewelryProductResource (Layer 5/9)
 * Full-featured Filament resource for managing the multi-tenant jewelry catalog.
 */
class JewelryProductResource extends Resource
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->copyable()
                    ->badge(),
                Tables\Columns\TextColumn::make('price_b2c')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (JewelryProduct $record) => $record->price_b2c / 100)
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_certification')
                    ->boolean()
                    ->label('Cert'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_published'),
                Tables\Columns\BadgeColumn::make('tags')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->relationship('store', 'name'),
                Tables\Filters\TernaryFilter::make('has_certification'),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\SelectFilter::make('metal_type')
                    ->options([
                        'yellow-gold' => 'Yellow Gold',
                        'white-gold' => 'White Gold',
                        'platinum' => 'Platinum',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['store', 'category', 'collection'])
            ->latest();
    }
}
