<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Furniture\Models\FurnitureProduct;
use App\Domains\Furniture\Models\FurnitureStore;
use App\Domains\Furniture\Models\FurnitureCategory;
use App\Domains\Furniture\Models\FurnitureRoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * FurnitureProductResource (Layer 6/9)
 * Full-featured Filament resource for managing interior furniture catalog.
 */
class FurnitureProductResource extends Resource
{
    protected static ?string $model = FurnitureProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationGroup = 'Furniture Marketplace';
    protected static ?string $navigationLabel = 'Interior Catalog';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Core Catalog Details')
                    ->columns(2)
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
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->hint('Commercial name of the furniture item.'),
                        Forms\Components\TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(50)
                            ->hint('Stock Keeping Unit (Unique SKU).'),
                    ]),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('price_b2c')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks')
                            ->hint('Standard Retail Price.'),
                        Forms\Components\TextInput::make('price_b2b')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks')
                            ->hint('Wholesale B2B Price.'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->required()
                            ->default(0),
                    ]),

                Forms\Components\Section::make('Physical Engineering & AI Properties')
                    ->columns(2)
                    ->schema([
                        Forms\Components\KeyValue::make('dimensions')
                            ->label('Dimensions (HxWxD / cm)')
                            ->required()
                            ->helperText('Used for oversized delivery validation.'),
                        Forms\Components\TagsInput::make('tags')
                            ->required()
                            ->placeholder('Style (scandi, loft, modern), Material, etc.'),
                        Forms\Components\Select::make('recommended_room_types')
                            ->label('Recommended Room Placement')
                            ->multiple()
                            ->options(
                                FurnitureRoomType::pluck('name', 'id')
                            )
                            ->preload(),
                    ]),

                Forms\Components\Section::make('Status & Visuals')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_oversized')
                            ->label('Oversized Item')
                            ->required()
                            ->hint('Requires special delivery terms.'),
                        Forms\Components\Toggle::make('has_3d_preview')
                            ->label('3D Preview Available')
                            ->required(),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published in Catalog')
                            ->required(),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListFurnitureProduct::route('/'),
            'create' => Pages\\CreateFurnitureProduct::route('/create'),
            'edit' => Pages\\EditFurnitureProduct::route('/{record}/edit'),
            'view' => Pages\\ViewFurnitureProduct::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListFurnitureProduct::route('/'),
            'create' => Pages\\CreateFurnitureProduct::route('/create'),
            'edit' => Pages\\EditFurnitureProduct::route('/{record}/edit'),
            'view' => Pages\\ViewFurnitureProduct::route('/{record}'),
        ];
    }
}
