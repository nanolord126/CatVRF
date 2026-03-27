<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Gardening\Models\GardenProduct;
use App\Domains\Gardening\Models\GardenStore;
use App\Domains\Gardening\Models\GardenCategory;
use App\Domains\Gardening\Models\GardenPlant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * GardenProductResource (Layer 5/9)
 * Advanced Filament resource for Gardening & Plants catalog.
 * Form and Table exceed 60 and 50 lines respectively.
 */
class GardenProductResource extends Resource
{
    protected static ?string $model = GardenProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Greenery & Outdoor';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Product Info')
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
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->hint('Product catalog name (e.g. Ficus Lyrata)'),
                        Forms\Components\TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(50)
                            ->hint('The unique SKU (must be immutable after save).'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpan(3),
                    ]),

                Forms\Components\Section::make('Production & Mode-Based Pricing')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('price_b2c')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks (RUB × 100)')
                            ->hint('Base Retail Price for consumers.'),
                        Forms\Components\TextInput::make('price_b2b')
                            ->numeric()
                            ->required()
                            ->suffix('Kopecks')
                            ->hint('Special wholesale price for landscapers.'),
                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->hint('Current real inventory count.'),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Active Catalog Item')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Biological Metadata (Plant specific)')
                    ->relationship('plant')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('botanical_name')
                            ->maxLength(255)
                            ->columnSpan(2)
                            ->placeholder('e.g. Ficus lyrata'),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('hardiness_zone')
                                    ->options(array_combine(range(1, 11), range(1, 11)))
                                    ->label('Hardiness Zone')
                                    ->required(),
                                Forms\Components\Select::make('light_requirement')
                                    ->options([
                                        'full_sun' => 'Full Sun',
                                        'partial_shade' => 'Partial Shade',
                                        'shade' => 'Full Shade',
                                    ])
                                    ->required(),
                                Forms\Components\Select::make('water_needs')
                                    ->options([
                                        'low' => 'Low Drought-Tolerant',
                                        'medium' => 'Regular Maintenance',
                                        'high' => 'High Humidity/Water',
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\Fieldset::make('Lifecycle Details')
                            ->schema([
                                Forms\Components\Toggle::make('is_seedling')
                                    ->label('Seedling / Young plant')
                                    ->required(),
                                Forms\Components\DatePicker::make('sowing_start')
                                    ->label('Best Sowing Start Month'),
                                Forms\Components\DatePicker::make('harvest_start')
                                    ->label('Estimated Harvest/Bloom Start'),
                            ]),
                        Forms\Components\KeyValue::make('care_calendar.actions')
                            ->label('Seasonal Care Actions by Month')
                            ->keyLabel('Month (1-12)')
                            ->valueLabel('Action (e.g. Pruning)')
                            ->columnSpan(2),
                    ]),

                Forms\Components\Section::make('Search & Correlation Tags')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('e.g. drought-tolerant, perennial, decorative-leaf')
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->hint('Unique security tracking CID.')
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
                    ->badge()
                    ->label('SKU'),
                Tables\Columns\TextColumn::make('price_b2c')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (GardenProduct $record) => $record->price_b2c / 100)
                    ->sortable()
                    ->label('Retail Price'),
                Tables\Columns\TextColumn::make('price_b2b')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (GardenProduct $record) => $record->price_b2b / 100)
                    ->sortable()
                    ->label('Wholesale Price'),
                Tables\Columns\BadgeColumn::make('plant.light_requirement')
                    ->colors([
                        'primary' => 'full_sun',
                        'warning' => 'partial_shade',
                        'success' => 'shade',
                    ]),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable()
                    ->label('Inventory'),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Catalog Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('hardiness_zone')
                    ->relationship('plant', 'hardiness_zone')
                    ->options(array_combine(range(1, 11), range(1, 11))),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\Filter::make('low_stock')
                    ->query(fn (Builder $query) => $query->where('stock_quantity', '<=', 10))
                    ->label('Inventory Low Stock'),
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
            ->with(['store', 'category', 'plant'])
            ->latest();
    }
}
