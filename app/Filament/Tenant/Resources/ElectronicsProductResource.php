<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Electronics\Models\ElectronicsProduct;
use App\Domains\Electronics\Models\ElectronicsStore;
use App\Domains\Electronics\Models\ElectronicsCategory;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Log;

/**
 * ElectronicsProductResource - Management of Gadgets, Components, and Accessories.
 * Layer: Filament UI & B2B/B2C Dashboard (5/9)
 * Requirement: Final class, full validation, audit log, correlation_id tracking.
 */
final class ElectronicsProductResource extends Resource
{
    protected static ?string $model = ElectronicsProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Electronics Vertical';
    protected static ?string $navigationLabel = 'Gadgets & Products';
    protected static ?string $slug = 'gadgets';

    /**
     * Define the complex form for electronic products.
     * Incorporates: Specs (JSONB), Images, Pricing (B2C/B2B), and Store mapping.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Core Information')
                    ->description('Primary gadget details and SKU mapping')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. iPhone 15 Pro Max'),
                        
                        TextInput::make('sku')
                            ->default(fn() => 'EL-' . strtoupper(Str::random(8)))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        
                        TextInput::make('brand')
                            ->required()
                            ->maxLength(100),
                        
                        Select::make('electronics_category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->required(),
                        
                        Select::make('electronics_store_id')
                            ->relationship('store', 'name', 
                                fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id)
                            )
                            ->required(),
                    ])->columns(2),

                Section::make('Pricing & B2B Logic')
                    ->description('All prices in kopecks (int)')
                    ->schema([
                        TextInput::make('price')
                            ->label('B2C Retail Price (kopecks)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix('cop'),
                            
                        TextInput::make('b2b_price')
                            ->label('B2B Wholesale Price (kopecks)')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Special price for business accounts with valid INN')
                            ->suffix('cop'),
                        
                        Toggle::make('is_b2b_available')
                            ->label('Enable for B2B Clients')
                            ->default(true),
                    ])->columns(3),

                Section::make('Technical Specifications & Metadata')
                    ->description('Hardware details stored in specs JSONB column')
                    ->schema([
                        KeyValue::make('specs')
                            ->label('Technical Specs')
                            ->keyLabel('Feature (e.g. RAM, OS, Battery)')
                            ->valueLabel('Value (e.g. 16GB, Android 14)')
                            ->default([
                                'os' => 'v14.0',
                                'ram' => '8GB',
                                'storage' => '256GB',
                                'is_5g' => 'true',
                            ]),
                            
                        KeyValue::make('package_contents')
                            ->label('In the Box')
                            ->keyLabel('Item')
                            ->valueLabel('Quantity')
                            ->default([
                                'Device' => '1',
                                'Charging Cable' => '1',
                            ]),
                    ])->columns(2),

                Section::make('Media & Status')
                    ->schema([
                        FileUpload::make('images')
                            ->multiple()
                            ->directory('electronics-products')
                            ->image()
                            ->imageEditor()
                            ->maxFiles(5),

                        Select::make('availability_status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'low_stock' => 'Low Stock (Alert)',
                                'out_of_stock' => 'Out of Stock',
                                'pre_order' => 'Pre-Order',
                                'refurbished' => 'Refurbished',
                            ])
                            ->default('in_stock')
                            ->required(),
                        
                        TextInput::make('warranty_months')
                            ->numeric()
                            ->default(12)
                            ->suffix('months'),
                    ])->columns(2),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListElectronicsProduct::route('/'),
            'create' => Pages\\CreateElectronicsProduct::route('/create'),
            'edit' => Pages\\EditElectronicsProduct::route('/{record}/edit'),
            'view' => Pages\\ViewElectronicsProduct::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListElectronicsProduct::route('/'),
            'create' => Pages\\CreateElectronicsProduct::route('/create'),
            'edit' => Pages\\EditElectronicsProduct::route('/{record}/edit'),
            'view' => Pages\\ViewElectronicsProduct::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListElectronicsProduct::route('/'),
            'create' => Pages\\CreateElectronicsProduct::route('/create'),
            'edit' => Pages\\EditElectronicsProduct::route('/{record}/edit'),
            'view' => Pages\\ViewElectronicsProduct::route('/{record}'),
        ];
    }
}
