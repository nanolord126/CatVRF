<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\DriverResource;

use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Driver;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Taxi Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('license_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_available')
                    ->required(),
                Forms\Components\TextInput::make('vehicle_id')
                    ->maxLength(36),
                Forms\Components\TextInput::make('tenant_id')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('license_number')->searchable(),
                Tables\Columns\IconColumn::make('is_available')->boolean(),
                Tables\Columns\TextColumn::make('vehicle_id')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Taxi\DriverResource\Pages\ListDrivers::route('/'),
            'create' => \App\Filament\Tenant\Resources\Taxi\DriverResource\Pages\CreateDriver::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Taxi\DriverResource\Pages\EditDriver::route('/{record}/edit'),
        ];
    }
}
