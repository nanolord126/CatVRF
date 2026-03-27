<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\TaxiDriver;
use App\Filament\Tenant\Resources\TaxiDriverResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TaxiDriverResource extends Resource
{
    protected static ?string $model = TaxiDriver::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Такси и Поездки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Личные данные')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('full_name')
                            ->required(),
                        Forms\Components\TextInput::make('license_number')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->disabled()
                            ->default(5.0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('ФИО')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('license_number')->label('Лицензия'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
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
            'index' => Pages\ListTaxiDrivers::route('/'),
            'create' => Pages\CreateTaxiDriver::route('/create'),
            'edit' => Pages\EditTaxiDriver::route('/{record}/edit'),
        ];
    }
}
