<?php

namespace App\Filament\Tenant\Resources\Marketplace\Taxi;

use App\Models\Tenants\TaxiTrip;
use App\Models\Tenants\TaxiDriverProfile;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class TaxiDispatcherConsole extends Resource
{
    protected static ?string $model = TaxiTrip::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationGroup = '🚕 Taxi Management';

    protected static ?string $slug = 'taxi-dispatcher-console';

    protected static ?string $modelLabel = 'Диспетчерская';

    protected static ?string $pluralModelLabel = 'Диспетчерская консоль';

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'searching' => 'danger',
                        'accepted', 'on_way' => 'info',
                        'in_trip' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('origin_address')
                    ->label('Откуда')
                    ->limit(30),
                Tables\Columns\TextColumn::make('destination_address')
                    ->label('Куда')
                    ->limit(30),
                Tables\Columns\TextColumn::make('fare')
                    ->label('Стоимость')
                    ->money('RUB'),
                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Водитель')
                    ->placeholder('Поиск водителя...'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'searching' => 'Поиск',
                        'accepted' => 'Принят',
                        'in_trip' => 'В пути',
                    ])
                    ->default('searching'),
            ])
            ->actions([
                Action::make('assign_driver')
                    ->label('Назначить')
                    ->icon('heroicon-m-user-plus')
                    ->hidden(fn (TaxiTrip $record) => $record->status !== 'searching')
                    ->form([
                        Forms\Components\Select::make('driver_id')
                            ->label('Свободные водители')
                            ->options(
                                TaxiDriverProfile::query()
                                    ->where('is_online', true)
                                    ->with('user')
                                    ->get()
                                    ->pluck('user.name', 'user_id')
                            )
                            ->required(),
                    ])
                    ->action(function (TaxiTrip $record, array $data) {
                        $record->update([
                            'driver_id' => $data['driver_id'],
                            'status' => 'accepted',
                        ]);

                        Notification::make()
                            ->title('Водитель назначен')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TaxiDispatcherConsole\Pages\ListDispatcher::route('/'),
        ];
    }
}
