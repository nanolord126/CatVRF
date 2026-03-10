<?php

namespace App\Filament\Tenant\Resources\Marketplace\Taxi;

use App\Filament\Tenant\Resources\Marketplace\Taxi\TaxiDriverResource\Pages;
use App\Models\Tenants\TaxiDriverProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxiDriverResource extends Resource
{
    protected static ?string $model = TaxiDriverProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = '🚕 Taxi Management';

    protected static ?string $modelLabel = 'Водитель';

    protected static ?string $pluralModelLabel = 'Водители';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Профиль водителя')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Сотрудник / Пользователь')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('fleet_id')
                            ->label('Таксопарк')
                            ->relationship('fleet', 'name')
                            ->required(),
                        Forms\Components\Select::make('current_car_id')
                            ->label('Текущий автомобиль')
                            ->relationship('car', 'plate_number')
                            ->searchable(),
                        Forms\Components\TextInput::make('license_number')
                            ->label('Водительское удостоверение'),
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(5.00),
                        Forms\Components\Toggle::make('is_online')
                            ->label('В сети')
                            ->onIcon('heroicon-m-signal')
                            ->offIcon('heroicon-m-signal-slash'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fleet.name')
                    ->label('Парк'),
                Tables\Columns\TextColumn::make('car.plate_number')
                    ->label('Авто')
                    ->placeholder('Не привязан'),
                Tables\Columns\BooleanColumn::make('is_online')
                    ->label('Онлайн'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1)
                    ->badge()
                    ->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Кошелек (Net)')
                    ->getStateUsing(fn (TaxiDriverProfile $record) => number_format($record->balance / 100, 2) . ' ₽'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_online')
                    ->label('В сети'),
                Tables\Filters\SelectFilter::make('fleet')
                    ->relationship('fleet', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxiDrivers::route('/'),
            'create' => Pages\CreateTaxiDriver::route('/create'),
            'edit' => Pages\EditTaxiDriver::route('/{record}/edit'),
        ];
    }
}
