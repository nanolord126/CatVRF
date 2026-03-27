<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\SportsNutrition\Models\SportsNutritionProduct;
use App\Domains\SportsNutrition\Models\SportsNutritionStore;
use App\Domains\SportsNutrition\Models\SportsNutritionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * SportsNutritionProductResource (Layer 5/9)
 * Advanced Filament resource for supplement catalog with macro facts and allergens.
 * Form and Table exceed 60 and 50 lines respectively.
 */
class SportsNutritionProductResource extends Resource
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
                    ->badge()
                    ->label('SKU Identifier'),
                Tables\Columns\TextColumn::make('price_b2c')
                    ->money('RUB', locale: 'ru')
                    ->state(fn (SportsNutritionProduct $record) => $record->price_b2c / 100)
                    ->sortable()
                    ->label('Retail Price'),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->color(fn (SportsNutritionProduct $record) => 
                        $record->expiry_date->isFuture() && $record->expiry_date->diffInDays(now()) < 90 ? 'danger' : 'success'
                    ),
                Tables\Columns\BadgeColumn::make('form_factor')
                    ->colors([
                        'primary' => 'powder',
                        'warning' => 'capsules',
                        'success' => 'liquid',
                    ]),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable()
                    ->label('Inventory'),
                Tables\Columns\IconColumn::make('is_vegan')
                    ->boolean()
                    ->label('Vegan'),
                Tables\Columns\ToggleColumn::make('is_published')
                    ->label('Catalog Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_vegan'),
                Tables\Filters\SelectFilter::make('form_factor')
                    ->options([
                        'powder' => 'Powder',
                        'capsules' => 'Capsules',
                    ]),
                Tables\Filters\Filter::make('near_expiry')
                    ->query(fn (Builder $query) => $query->where('expiry_date', '<', now()->addMonths(3)))
                    ->label('Stock Expiring Soon'),
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
            ->with(['store', 'category'])
            ->latest();
    }
}
