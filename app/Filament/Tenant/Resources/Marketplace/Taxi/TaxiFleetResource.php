<?php

namespace App\Filament\Tenant\Resources\Marketplace\Taxi;

use App\Filament\Tenant\Resources\Marketplace\Taxi\TaxiFleetResource\Pages;
use App\Models\Tenants\TaxiFleet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TaxiFleetResource extends Resource
{
    protected static ?string $model = TaxiFleet::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = '🚕 Taxi Management';

    protected static ?string $modelLabel = 'Таксопарк';

    protected static ?string $pluralModelLabel = 'Автопарки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Комиссия парка (%)')
                            ->numeric()
                            ->default(5.00)
                            ->required(),
                        Forms\Components\Hidden::make('correlation_id')
                            ->default(fn () => (string) Str::uuid()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Комиссия (%)')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс (Wallet)')
                    ->getStateUsing(fn (TaxiFleet $record) => number_format($record->balance / 100, 2) . ' ₽')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('drivers_count')
                    ->label('Водителей')
                    ->counts('drivers'),
                Tables\Columns\TextColumn::make('cars_count')
                    ->label('Автомобилей')
                    ->counts('cars'),
            ])
            ->filters([])
            ->actions([
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
            'index' => Pages\ListTaxiFleets::route('/'),
            'create' => Pages\CreateTaxiFleet::route('/create'),
            'edit' => Pages\EditTaxiFleet::route('/{record}/edit'),
        ];
    }
}
