<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CarRental;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class BookingResource extends Resource
{

    protected static ?string $model = Booking::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

        protected static ?string $navigationGroup = 'Car Rental Management';

        protected static ?string $navigationLabel = 'Rentals (Bookings)';

        /**
         * Comprehensive Booking Cycle (Canon 2026 Table Mapping).
         * Includes: Reactive calculation, B2B logic.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Card::make()
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('client_id')
                                        ->relationship('client', 'name')
                                        ->required()
                                        ->searchable()
                                        ->placeholder('Search Client'),

                                    Forms\Components\Select::make('car_id')
                                        ->relationship('car', 'brand', modifyQueryUsing: fn (Builder $query) => $query->where('status', 'available'))
                                        ->required()
                                        ->preload()
                                        ->searchable()
                                        ->label('Available Car')
                                        ->live() // Essential for reactive pricing
                                        ->getOptionLabelFromRecordUsing(fn (Car $record) => "{$record->brand} {$record->model} [{$record->license_plate}]"),

                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'pending' => 'Pending/Reserved',
                                            'active' => 'Active (In Use)',
                                            'completed' => 'Completed (Returned)',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->required()
                                        ->default('pending'),
                                ]),

                            Forms\Components\Section::make('Schedule and Pricing')
                                ->schema([
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\DateTimePicker::make('start_at')
                                                ->required()
                                                ->label('Pick-up Time')
                                                ->live(),

                                            Forms\Components\DateTimePicker::make('end_at')
                                                ->required()
                                                ->label('Return Time')
                                                ->live(),

                                            Forms\Components\TextInput::make('total_price')
                                                ->numeric()
                                                ->required()
                                                ->prefix('₽')
                                                ->label('Calculated Total')
                                                ->disabled()
                                                ->dehydrated(), // Save even if disabled
                                        ]),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Toggle::make('is_b2b')
                                                ->label('B2B Commercial Rental')
                                                ->live()
                                                ->default(false),

                                            Forms\Components\TextInput::make('deposit_held')
                                                ->numeric()
                                                ->prefix('₽')
                                                ->placeholder('e.g. 5000'),
                                        ]),
                                ])
                                ->columns(1)
                                ->collapsible()
                                // Reactive logic: Recalculate price on change of car/dates/b2b
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $carId = $get('car_id');
                                    $start = $get('start_at');
                                    $end = $get('end_at');
                                    $isB2B = $get('is_b2b');

                                    if ($carId && $start && $end) {
                                        $car = Car::find($carId);
                                        $diff = strtotime($end) - strtotime($start);
                                        $days = max(1, (int) round($diff / (60 * 60 * 24))); // Minimum 1 day

                                        $pricing = app(PricingService::class)->calculate($car, $days, $isB2B);
                                        $set('total_price', $pricing['total_price']);
                                    }
                                }),

                            Forms\Components\Section::make('Security and Metadata (JSONB)')
                                ->schema([
                                    Forms\Components\FileUpload::make('pickup_photos')
                                        ->multiple()
                                        ->directory('rentals/pickup')
                                        ->label('Check-in Evidence (Pickup Photos)'),

                                    Forms\Components\FileUpload::make('return_photos')
                                        ->multiple()
                                        ->directory('rentals/return')
                                        ->label('Check-out Evidence (Return Photos)'),

                                    Forms\Components\KeyValue::make('metadata')
                                        ->keyLabel('Meta Attribute')
                                        ->valueLabel('Value (e.g. Child seat, GPS, Wi-Fi)')
                                        ->default([
                                            'fuel_at_pickup' => 'Full',
                                            'cleanliness' => 'High',
                                            'insurance_tier' => 'Standard',
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
         * Active Rental Table (Layer 6: UI View).
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('client.name')
                        ->label('Renter')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('car.brand')
                        ->label('Vehicle')
                        ->getStateUsing(fn (Booking $record) => "{$record->car->brand} {$record->car->model}")
                        ->searchable(['car.brand', 'car.model']),

                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'primary' => 'pending',
                            'warning' => 'active',
                            'success' => 'completed',
                            'danger' => 'cancelled',
                        ]),

                    Tables\Columns\TextColumn::make('start_at')
                        ->dateTime()
                        ->label('Check-in')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('total_price')
                        ->money('RUB')
                        ->label('Total (₽)')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'active' => 'Active',
                            'completed' => 'Completed',
                        ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBookings::route('/'),
                'create' => Pages\CreateBooking::route('/create'),
                'edit' => Pages\EditBooking::route('/{record}/edit'),
            ];
        }
}
