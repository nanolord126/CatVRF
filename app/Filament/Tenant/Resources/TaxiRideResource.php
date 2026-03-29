<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\TaxiRide;
use App\Domains\Auto\Services\TaxiService;
use App\Domains\Auto\Services\SurgePricingService;
use App\Services\FraudMLService;
use App\Filament\Tenant\Resources\TaxiRideResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

final class TaxiRideResource extends Resource
{
    protected static ?string $model = TaxiRide::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Такси и Поездки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Маршрут')
                    ->schema([
                        Forms\Components\Select::make('passenger_id')
                            ->relationship('passenger', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('driver_id')
                            ->relationship('driver', 'full_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('pickup_address')
                            ->required(),
                        Forms\Components\TextInput::make('dropoff_address')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Экономика')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('коп.')
                            ->required(),
                        Forms\Components\TextInput::make('surge_multiplier')
                            ->numeric()
                            ->step(0.1)
                            ->default(1.0),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Ожидание',
                                'finding_driver' => 'Поиск водителя',
                                'driver_assigned' => 'Водитель назначен',
                                'on_way' => 'В пути',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->required(),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListTaxiRide::route('/'),
            'create' => Pages\\CreateTaxiRide::route('/create'),
            'edit' => Pages\\EditTaxiRide::route('/{record}/edit'),
            'view' => Pages\\ViewTaxiRide::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListTaxiRide::route('/'),
            'create' => Pages\\CreateTaxiRide::route('/create'),
            'edit' => Pages\\EditTaxiRide::route('/{record}/edit'),
            'view' => Pages\\ViewTaxiRide::route('/{record}'),
        ];
    }
}
