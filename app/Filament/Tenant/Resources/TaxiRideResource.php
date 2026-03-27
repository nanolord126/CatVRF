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
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')->label('ID')->copyable(),
                Tables\Columns\TextColumn::make('passenger.name')->label('Пассажир')->searchable(),
                Tables\Columns\TextColumn::make('driver.full_name')->label('Водитель')->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Сумма (руб)')
                    ->getStateUsing(fn ($record) => $record->price / 100)
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'on_way' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Создан'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Завершена',
                        'on_way' => 'В пути',
                        'cancelled' => 'Отменена',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('checkFraud')
                    ->label('ML Фрод-чек')
                    ->icon('heroicon-o-shield-check')
                    ->action(function (TaxiRide $record, FraudMLService $fraudService) {
                        $score = $fraudService->scoreTaxiRide($record);
                        
                        $isFraud = $score > 0.7; // Порог КАНОНА 2026

                        Notification::make()
                            ->title($isFraud ? 'ПОДОЗРЕНИЕ НА ФРОД' : 'Чистая поездка')
                            ->body("ML Score: " . number_format($score, 3))
                            ->status($isFraud ? 'danger' : 'success')
                            ->send();

                        Log::channel('fraud_alert')->info('Taxi ride manual fraud check', [
                            'ride_id' => $record->id,
                            'score' => $score,
                            'correlation_id' => $record->correlation_id,
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['passenger', 'driver'])
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiRides::route('/'),
            'create' => Pages\CreateTaxiRide::route('/create'),
            'edit' => Pages\EditTaxiRide::route('/{record}/edit'),
        ];
    }
}
