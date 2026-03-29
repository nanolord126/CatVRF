<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Stationery\Models\StationeryProduct;
use App\Domains\Stationery\Models\StationeryStore;
use App\Domains\Stationery\Models\StationeryCategory;
use App\Services\AI\AIStationeryConstructor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;

/**
 * StationeryProductResource.
 * Comprehensive management for office and school supplies.
 * Features B2B prices, stock alerts, and AI popularity scoring.
 */
class StationeryProductResource extends Resource
{
    protected static ?string $model = StationeryProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Stationery Hub';
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    /**
     * Deep Form implementation (>60 lines per CANON 2026).
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Split::make([
                Forms\Components\Section::make('General Information')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->autofocus()
                        ->placeholder('Premium Leather Notebook'),

                    Forms\Components\Select::make('store_id')
                        ->relationship('store', 'name')
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name')
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('sku')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->label('SKU / Barcode'),

                    Forms\Components\RichEditor::make('description')
                        ->columnSpanFull()
                        ->placeholder('Detailed description for office buyers...'),
                ])->columns(2),

                Forms\Components\Section::make('Pricing & Business Mode')->schema([
                    Forms\Components\TextInput::make('price_cents')
                        ->numeric()
                        ->prefix('RUB')
                        ->label('Retail Price (Cents)')
                        ->required(),

                    Forms\Components\TextInput::make('b2b_price_cents')
                        ->numeric()
                        ->prefix('RUB')
                        ->label('B2B Price (Cents)'),

                    Forms\Components\Toggle::make('has_gift_wrapping')
                        ->label('Gift Wrapping Available')
                        ->reactive(),

                    Forms\Components\TextInput::make('gift_wrap_price_cents')
                        ->numeric()
                        ->prefix('RUB')
                        ->label('Wrap Price')
                        ->visible(fn (callable $get) => $get('has_gift_wrapping')),
                ])->columns(2),
            ])->columnSpanFull(),

            Forms\Components\Section::make('Inventory & Parameters')->schema([
                Forms\Components\TextInput::make('stock_quantity')
                    ->numeric()
                    ->required()
                    ->default(0),

                Forms\Components\TextInput::make('min_stock_threshold')
                    ->numeric()
                    ->required()
                    ->default(10),

                Forms\Components\KeyValue::make('attributes')
                    ->label('Product Attributes (Brand, Size, Weight, Material)')
                    ->required(),

                Forms\Components\TextInput::make('tags')
                    ->placeholder('art, school, premium')
                    ->label('Search Tags'),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->onColor('success')
                    ->offColor('danger'),
            ])->columns(3),
        ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListStationeryProduct::route('/'),
            'create' => Pages\\CreateStationeryProduct::route('/create'),
            'edit' => Pages\\EditStationeryProduct::route('/{record}/edit'),
            'view' => Pages\\ViewStationeryProduct::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListStationeryProduct::route('/'),
            'create' => Pages\\CreateStationeryProduct::route('/create'),
            'edit' => Pages\\EditStationeryProduct::route('/{record}/edit'),
            'view' => Pages\\ViewStationeryProduct::route('/{record}'),
        ];
    }
}
