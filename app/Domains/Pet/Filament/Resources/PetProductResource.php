<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use Filament\Resources\Resource;

final class PetProductResource extends Resource
{

    protected static ?string $model = PetProduct::class;

        protected static ?string $slug = 'pet-products';

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

        protected static ?string $navigationGroup = 'Pet Services';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Select::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('description')
                        ->maxLength(1000),
                    TextInput::make('sku')
                        ->unique(ignoreRecord: true)
                        ->required(),
                    Select::make('pet_type')
                        ->options(['dog' => 'Dog', 'cat' => 'Cat', 'bird' => 'Bird', 'rabbit' => 'Rabbit', 'all' => 'All'])
                        ->required(),
                    Select::make('category')
                        ->options(['food' => 'Food', 'toys' => 'Toys', 'accessories' => 'Accessories', 'medicine' => 'Medicine'])
                        ->required(),
                    TextInput::make('price')
                        ->numeric()
                        ->required(),
                    TextInput::make('cost_price')
                        ->numeric(),
                    TextInput::make('current_stock')
                        ->numeric()
                        ->default(0),
                    TextInput::make('min_stock_threshold')
                        ->numeric()
                        ->default(5),
                    Toggle::make('is_active')
                        ->default(true),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('clinic.name')
                        ->searchable(),
                    TextColumn::make('sku')
                        ->searchable(),
                    TextColumn::make('price')
                        ->money('RUB'),
                    TextColumn::make('current_stock')
                        ->numeric(),
                    IconColumn::make('is_active')->boolean(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    //
                ])
                ->bulkActions([
                    //
                ]);
        }
}
