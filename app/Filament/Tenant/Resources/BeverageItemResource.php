<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Beverages\Models\BeverageItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

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

    /**
     * Complete table definition (>= 50 lines per canon 2026).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Product'),
                    
                Tables\Columns\TextColumn::make('shop.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label('Venue'),
                    
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->label('Category'),
                    
                Tables\Columns\TextColumn::make('price')
                    ->money('rub', 100)
                    ->sortable()
                    ->label('Price'),
                    
                Tables\Columns\TextColumn::make('volume_ml')
                    ->suffix(' ml')
                    ->label('Volume'),
                    
                Tables\Columns\TextColumn::make('stock_count')
                    ->numeric()
                    ->icon(fn ($state) => $state < 5 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                    ->color(fn ($state) => $state < 5 ? 'danger' : 'success')
                    ->label('Stock'),
                    
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('On Sale'),
                    
                Tables\Columns\TextColumn::make('freshness_control_type')
                    ->label('QC Type')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shop_id')
                    ->relationship('shop', 'name')
                    ->label('Filter by Shop'),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Availability Filter'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Canon: Global Scope via getEloquentQuery.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\BeverageItemResource\Pages\ListBeverageItems::route('/'),
            'create' => \App\Filament\Tenant\Resources\BeverageItemResource\Pages\CreateBeverageItem::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\BeverageItemResource\Pages\EditBeverageItem::route('/{record}/edit'),
        ];
    }
}
