<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use App\Domains\Taxi\Models\TaxiRide;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

final class TaxiRideResource extends Resource
{
    protected static ?string $model = TaxiRide::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right';

    protected static ?string $navigationGroup = 'Auto';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'full_name')
                    ->required(),

                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'license_plate')
                    ->required(),

                Forms\Components\TextInput::make('pickup_point')
                    ->required(),

                Forms\Components\TextInput::make('dropoff_point')
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('surge_multiplier')
                    ->numeric()
                    ->default(1.0),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('driver.full_name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('vehicle.license_plate')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('price')
                    ->money('RUB'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\ListTaxiRides::route('/'),
            'create' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\CreateTaxiRide::route('/create'),
            'view' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\ViewTaxiRide::route('/{record}'),
            'edit' => \App\Filament\Tenant\Resources\Auto\TaxiRideResource\Pages\EditTaxiRide::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with(['driver', 'vehicle']);
    }
}
