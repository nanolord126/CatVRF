<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party;

final class PartyProductResource extends Resource
{

    protected static ?string $model = PartyProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-gift';
        protected static ?string $navigationGroup = 'Party Supplies';
        protected static ?string $modelLabel = 'Festive Item';
        protected static ?string $pluralModelLabel = 'Festive Items';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Product Basics')
                        ->description('Essential identity and pricing.')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('sku')
                                ->required()
                                ->label('SKU Code')
                                ->unique(ignoreRecord: true),
                            Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                            TextInput::make('price_cents')
                                ->required()
                                ->numeric()
                                ->label('Retail Price (Cents)')
                                ->default(0),
                            TextInput::make('current_stock')
                                ->required()
                                ->numeric()
                                ->label('Stock Available')
                                ->default(0),
                        ])->columns(2),

                    Section::make('Relations & Metadata')
                        ->description('Theme, category and custom attributes.')
                        ->schema([
                            Select::make('party_store_id')
                                ->label('Store')
                                ->relationship('store', 'name')
                                ->required(),
                            Select::make('party_category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->required(),
                            Select::make('party_theme_id')
                                ->label('Theme/Collection')
                                ->relationship('theme', 'name'),
                            Toggle::make('is_b2b')
                                ->label('Wholesale/B2B Item'),
                            Toggle::make('is_active')
                                ->label('Active in Catalog')
                                ->default(true),
                            KeyValue::make('attributes')
                                ->keyLabel('Feature')
                                ->valueLabel('Value'),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->limit(30),
                    TextColumn::make('sku')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('price_cents')
                        ->money('RUB') // Simulated RUB cents
                        ->sortable(),
                    TextColumn::make('current_stock')
                        ->sortable()
                        ->label('Stock'),
                    BooleanColumn::make('is_b2b')
                        ->label('B2B'),
                    BooleanColumn::make('is_active'),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    // (Optional) Add category/theme filters here
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            $query = parent::getEloquentQuery();

            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }

            return $query;
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Party\PartyProductResource\Pages\ListPartyProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Party\PartyProductResource\Pages\CreatePartyProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Party\PartyProductResource\Pages\EditPartyProduct::route('/{record}/edit'),
            ];
        }
}
