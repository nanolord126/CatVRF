<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VehicleRentalResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = VehicleRental::class;

        protected static ?string $navigationLabel = 'Аренда авто';

        protected static ?string $pluralModelLabel = 'Аренда автомобилей';

        protected static ?string $navigationGroup = 'Авто';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация об аренде')
                    ->schema([
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Автомобиль')
                            ->relationship('vehicle', 'license_plate')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('renter_id')
                            ->label('Арендатор')
                            ->relationship('renter', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\DateTimePicker::make('start_datetime')
                            ->label('Начало аренды')
                            ->required(),

                        Forms\Components\DateTimePicker::make('end_datetime')
                            ->label('Конец аренды')
                            ->required(),

                        Forms\Components\TextInput::make('daily_rate')
                            ->label('Стоимость в сутки (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Общая стоимость (копейки)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('initial_mileage')
                            ->label('Пробег при выдаче (км)')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('final_mileage')
                            ->label('Пробег при возврате (км)')
                            ->numeric(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'active' => 'Активна',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Примечания')
                            ->columnSpanFull(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('vehicle.license_plate')
                        ->label('Автомобиль')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('renter.name')
                        ->label('Арендатор')
                        ->searchable(),

                    Tables\Columns\TextColumn::make('start_datetime')
                        ->label('Начало')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('end_datetime')
                        ->label('Конец')
                        ->dateTime('d.m.Y H:i')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('total_price')
                        ->label('Стоимость')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->label('Статус')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'active' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                            default => 'gray',
                        }),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'pending' => 'Ожидает',
                            'active' => 'Активна',
                            'completed' => 'Завершена',
                            'cancelled' => 'Отменена',
                        ]),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListVehicleRentals::route('/'),
                'create' => Pages\CreateVehicleRental::route('/create'),
                'edit' => Pages\EditVehicleRental::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}
