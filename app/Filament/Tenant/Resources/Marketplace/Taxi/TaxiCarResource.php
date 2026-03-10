<?php

namespace App\Filament\Tenant\Resources\Marketplace\Taxi;

use App\Filament\Tenant\Resources\Marketplace\Taxi\TaxiCarResource\Pages;
use App\Models\Tenants\TaxiCar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TaxiCarResource extends Resource
{
    protected static ?string $model = TaxiCar::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = '🚕 Taxi Management';

    protected static ?string $modelLabel = 'Автомобиль';

    protected static ?string $pluralModelLabel = 'Автопарк автомобилей';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали автомобиля')
                    ->schema([
                        Forms\Components\Select::make('fleet_id')
                            ->label('Таксопарк')
                            ->relationship('fleet', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('model')
                            ->label('Модель')
                            ->required()
                            ->placeholder('Toyota Camry'),
                        Forms\Components\TextInput::make('plate_number')
                            ->label('Гос. номер')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('class')
                            ->label('Класс')
                            ->options([
                                'economy' => 'Эконом',
                                'comfort' => 'Комфорт',
                                'business' => 'Бизнес',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активен',
                                'maintenance' => 'На ТО',
                                'inactive' => 'Неактивен',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\TextInput::make('color')
                            ->label('Цвет'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plate_number')
                    ->label('Гос. номер')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fleet.name')
                    ->label('Парк')
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активен',
                        'maintenance' => 'На ТО',
                        'inactive' => 'Неактивен',
                    ]),
                Tables\Columns\TextColumn::make('class')
                    ->label('Класс')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'business' => 'warning',
                        'comfort' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('fleet')
                    ->relationship('fleet', 'name'),
                Tables\Filters\SelectFilter::make('class')
                    ->options([
                        'economy' => 'Эконом',
                        'comfort' => 'Комфорт',
                        'business' => 'Бизнес',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTaxiCars::route('/'),
            'create' => Pages\CreateTaxiCar::route('/create'),
            'edit' => Pages\EditTaxiCar::route('/{record}/edit'),
        ];
    }
}
