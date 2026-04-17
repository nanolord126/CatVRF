<?php declare(strict_types=1);

namespace Modules\Taxi\Filament;

use Modules\Taxi\Models\TaxiRide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Filament Resource for Taxi Ride management in admin panel.
 * 
 * Provides CRUD operations, filtering, and analytics for taxi rides.
 * Follows CatVRF 2026 canon: tenant isolation, soft deletes, audit trails.
 */
final class TaxiRideResource extends Resource
{
    protected static ?string $model = TaxiRide::class;

    protected static ?string $navigationIcon = 'heroicon-o-taxi';

    protected static ?string $navigationGroup = 'Taxi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ride Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                TaxiRide::STATUS_REQUESTED => 'Requested',
                                TaxiRide::STATUS_ACCEPTED => 'Accepted',
                                TaxiRide::STATUS_STARTED => 'Started',
                                TaxiRide::STATUS_COMPLETED => 'Completed',
                                TaxiRide::STATUS_CANCELLED => 'Cancelled',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('passenger_id')
                            ->label('Passenger')
                            ->relationship('passenger', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('driver_id')
                            ->label('Driver')
                            ->relationship('driver', 'full_name')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Route')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_address')
                            ->label('Pickup Address')
                            ->required()
                            ->maxLength(500),
                        
                        Forms\Components\TextInput::make('dropoff_address')
                            ->label('Dropoff Address')
                            ->required()
                            ->maxLength(500),
                        
                        Forms\Components\TextInput::make('distance_meters')
                            ->label('Distance (meters)')
                            ->numeric()
                            ->required(),
                        
                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('base_price_kopeki')
                            ->label('Base Price (kopeki)')
                            ->numeric()
                            ->required(),
                        
                        Forms\Components\TextInput::make('final_price_kopeki')
                            ->label('Final Price (kopeki)')
                            ->numeric()
                            ->required(),
                        
                        Forms\Components\TextInput::make('surge_multiplier')
                            ->label('Surge Multiplier')
                            ->numeric()
                            ->step(0.1)
                            ->default(1.0),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('requested_at')
                            ->label('Requested At')
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('accepted_at')
                            ->label('Accepted At'),
                        
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At'),
                        
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Ratings')
                    ->schema([
                        Forms\Components\TextInput::make('passenger_rating')
                            ->label('Passenger Rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                        
                        Forms\Components\TextInput::make('driver_rating')
                            ->label('Driver Rating')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Cancellation')
                    ->schema([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->rows(3),
                    ])
                    ->visible(fn (callable $get) => $get('status') === TaxiRide::STATUS_CANCELLED),
                
                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->limit(10),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        TaxiRide::STATUS_REQUESTED => 'warning',
                        TaxiRide::STATUS_ACCEPTED => 'info',
                        TaxiRide::STATUS_STARTED => 'primary',
                        TaxiRide::STATUS_COMPLETED => 'success',
                        TaxiRide::STATUS_CANCELLED => 'danger',
                    }),
                
                Tables\Columns\TextColumn::make('passenger.name')
                    ->label('Passenger')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('driver.full_name')
                    ->label('Driver')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('pickup_address')
                    ->label('Pickup')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('dropoff_address')
                    ->label('Dropoff')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('final_price_kopeki')
                    ->label('Price (RUB)')
                    ->money('RUB')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('surge_multiplier')
                    ->label('Surge')
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2) . 'x'),
                
                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        TaxiRide::STATUS_REQUESTED => 'Requested',
                        TaxiRide::STATUS_ACCEPTED => 'Accepted',
                        TaxiRide::STATUS_STARTED => 'Started',
                        TaxiRide::STATUS_COMPLETED => 'Completed',
                        TaxiRide::STATUS_CANCELLED => 'Cancelled',
                    ]),
                
                Tables\Filters\Filter::make('high_surge')
                    ->label('High Surge (> 1.5x)')
                    ->query(fn (Builder $query): Builder => $query->where('surge_multiplier', '>', 1.5)),
                
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('requested_at', today())),
                
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('requested_at', [now()->startOfWeek(), now()->endOfWeek()])),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('requested_at', now()->month)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            'passenger',
            'driver',
            'vehicle',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Taxi\Filament\TaxiRideResource\Pages\ListTaxiRides::route('/'),
            'create' => \Modules\Taxi\Filament\TaxiRideResource\Pages\CreateTaxiRide::route('/create'),
            'view' => \Modules\Taxi\Filament\TaxiRideResource\Pages\ViewTaxiRide::route('/{record}'),
            'edit' => \Modules\Taxi\Filament\TaxiRideResource\Pages\EditTaxiRide::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
