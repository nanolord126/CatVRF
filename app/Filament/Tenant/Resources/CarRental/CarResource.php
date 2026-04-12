<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CarRental;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class CarResource extends Resource
{

    protected static ?string $model = Car::class;

        protected static ?string $navigationIcon = 'heroicon-o-truck'; // Car Icon

        protected static ?string $navigationGroup = 'Car Rental Management';

        protected static ?string $navigationLabel = 'Fleet (Vehicles)';

        /**
         * Comprehensive Form Structure (Canon 2026 Table Mapping).
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Card::make()
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('brand')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g. BMW'),

                                    Forms\Components\TextInput::make('model')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g. X5'),

                                    Forms\Components\TextInput::make('license_plate')
                                        ->required()
                                        ->unique(ignorable: fn ($record) => $record)
                                        ->placeholder('H777HH77'),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('company_id')
                                        ->relationship('company', 'name')
                                        ->required()
                                        ->preload()
                                        ->searchable(),

                                    Forms\Components\Select::make('type_id')
                                        ->relationship('type', 'name')
                                        ->required()
                                        ->preload()
                                        ->searchable(),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'available' => 'Available (In Fleet)',
                                            'rented' => 'Rented (In Use)',
                                            'maintenance' => 'Maintenance (Repair)',
                                            'reserved' => 'Reserved (Booking Hold)',
                                        ])
                                        ->required()
                                        ->default('available'),

                                    Forms\Components\TextInput::make('color')
                                        ->placeholder('e.g. Sapphire Black'),
                                ]),

                            Forms\Components\Section::make('Technical Specifications')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('vin')
                                                ->label('VIN Number')
                                                ->required()
                                                ->unique(ignorable: fn ($record) => $record)
                                                ->maxLength(17),

                                            Forms\Components\TextInput::make('year')
                                                ->numeric()
                                                ->required()
                                                ->minValue(2010)
                                                ->maxValue(date('Y') + 1),

                                            Forms\Components\TextInput::make('mileage')
                                                ->numeric()
                                                ->required()
                                                ->label('Current Mileage (km)')
                                                ->default(0),

                                            Forms\Components\TextInput::make('daily_price_override')
                                                ->numeric()
                                                ->label('Daily Price Overwrite (Leave null to use Type Price)')
                                                ->prefix('₽'),
                                        ]),
                                ])
                                ->collapsible(),

                            Forms\Components\Section::make('Media and Documents')
                                ->schema([
                                    Forms\Components\FileUpload::make('images')
                                        ->multiple()
                                        ->directory('cars/images')
                                        ->image()
                                        ->imageEditor()
                                        ->maxFiles(8),

                                    Forms\Components\KeyValue::make('features')
                                        ->label('Features (JSONB)')
                                        ->keyLabel('Feature Name')
                                        ->valueLabel('Value (e.g. Yes/No)')
                                        ->default([
                                            'AC' => 'Standard',
                                            'GPS' => 'Built-in',
                                            'Seats' => '5',
                                            'Body' => 'SUV',
                                        ]),
                                ])
                                ->collapsible(),

                            Forms\Components\Hidden::make('uuid')
                                ->default(fn () => (string) Str::uuid()),

                            Forms\Components\Hidden::make('correlation_id')
                                ->default(fn () => (string) Str::uuid()),
                        ])
                ]);
        }

        /**
         * Car Fleet Table (Layer 6: UI View).
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name') // Synthetic Brand + Model
                        ->label('Vehicle')
                        ->getStateUsing(fn (Car $record) => "{$record->brand} {$record->model}")
                        ->searchable(['brand', 'model']),

                    Tables\Columns\TextColumn::make('license_plate')
                        ->label('Plate')
                        ->badge()
                        ->color('primary')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('type.name')
                        ->badge()
                        ->label('Category'),

                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'success' => 'available',
                            'danger' => 'maintenance',
                            'warning' => 'rented',
                            'primary' => 'reserved',
                        ]),

                    Tables\Columns\TextColumn::make('mileage')
                        ->label('Distance (km)')
                        ->numeric()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('updated_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'available' => 'Available',
                            'rented' => 'Rented',
                            'maintenance' => 'Repair',
                        ]),

                    Tables\Filters\SelectFilter::make('type')
                        ->relationship('type', 'name'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\DeleteBulkAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListCars::route('/'),
                'create' => Pages\CreateCar::route('/create'),
                'edit' => Pages\EditCar::route('/{record}/edit'),
            ];
        }

        /**
         * Global Scope Integration (Canon 2026: Multi-tenant safety).
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['type', 'company']); // Eager loading performance
        }
}
