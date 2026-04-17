<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class MusicInstrumentResource extends Resource
{

    protected static ?string $model = MusicInstrument::class;

        protected static ?string $navigationIcon = 'heroicon-o-musical-note';

        protected static ?string $navigationGroup = 'Music Management';

        protected static ?string $navigationLabel = 'Instruments';

        /**
         * Build the form for creating and editing instruments.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Basic Information')
                        ->description('General instrument details.')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('music_store_id')
                                    ->relationship('store', 'name')
                                    ->label('Store')
                                    ->required()
                                    ->searchable(),
                                TextInput::make('name')
                                    ->label('Instrument Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Example: Fender Stratocaster'),
                                TextInput::make('brand')
                                    ->label('Brand')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Example: Fender'),
                                TextInput::make('model')
                                    ->label('Model')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Example: Player Series'),
                                Select::make('category')
                                    ->label('Category')
                                    ->options([
                                        'guitar' => 'Guitar',
                                        'piano' => 'Piano',
                                        'drums' => 'Drums',
                                        'violin' => 'Violin',
                                        'brass' => 'Brass',
                                        'synth' => 'Synthesizer',
                                        'folk' => 'Folk Instruments',
                                        'other' => 'Other',
                                    ])
                                    ->required(),
                                Select::make('condition')
                                    ->label('Condition')
                                    ->options([
                                        'new' => 'New',
                                        'used' => 'Used',
                                        'refurbished' => 'Refurbished',
                                    ])
                                    ->default('new')
                                    ->required(),
                            ]),
                        ]),

                    Section::make('Pricing and Inventory')
                        ->description('Setup price, rental pricing, and stock levels.')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('price_cents')
                                    ->label('Price (kopecks)')
                                    ->numeric()
                                    ->integer()
                                    ->required()
                                    ->suffix('₽ (копейки)')
                                    ->minValue(0),
                                TextInput::make('rental_price_cents')
                                    ->label('Rental Price / Day (kopecks)')
                                    ->numeric()
                                    ->integer()
                                    ->suffix('₽ (копейки)')
                                    ->minValue(0),
                                TextInput::make('stock')
                                    ->label('Current Stock')
                                    ->numeric()
                                    ->integer()
                                    ->required()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('hold_stock')
                                    ->label('Hold Stock (Rented)')
                                    ->numeric()
                                    ->integer()
                                    ->disabled()
                                    ->default(0),
                            ]),
                        ]),

                    Section::make('Specifications and Tags')
                        ->description('JSON-based specs and labels.')
                        ->schema([
                            Grid::make(1)->schema([
                                Textarea::make('specifications')
                                    ->label('Detailed Specs (Keywords)')
                                    ->rows(5)
                                    ->placeholder('Example: Alder body, Maple neck, 22 jumbo frets...'),
                                TagsInput::make('tags')
                                    ->label('Tags')
                                    ->placeholder('Add tags like #rock, #vintage, #jazz'),
                            ]),
                        ]),
                ]);
        }

        /**
         * Build the table for listing instruments.
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->label('Instrument')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('store.name')
                        ->label('Store')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('category')
                        ->label('Category')
                        ->badge()
                        ->color('info')
                        ->sortable(),
                    TextColumn::make('price_cents')
                        ->label('Price')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ₽')
                        ->sortable(),
                    TextColumn::make('rental_price_cents')
                        ->label('Rental Price')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2) . ' ₽/day' : 'N/A')
                        ->sortable(),
                    TextColumn::make('stock')
                        ->label('Stock')
                        ->numeric()
                        ->sortable()
                        ->color(fn ($state) => $state < 5 ? 'danger' : 'success'),
                    TextColumn::make('hold_stock')
                        ->label('Rented')
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('updated_at')
                        ->label('Last Updated')
                        ->dateTime()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('category')
                        ->options([
                            'guitar' => 'Guitars',
                            'piano' => 'Pianos',
                            'drums' => 'Drums',
                            'other' => 'Other',
                        ]),
                    SelectFilter::make('condition')
                        ->options([
                            'new' => 'New',
                            'used' => 'Used',
                        ]),
                ])
                ->actions([
                    EditAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ]);
        }

        /**
         * Get Eloquent query with tenant scoping and eager loaded relations.
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['store'])
                ->latest();
        }
}
