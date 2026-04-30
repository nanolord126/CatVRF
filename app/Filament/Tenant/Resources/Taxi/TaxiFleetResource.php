<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi\TaxiFleetResource;

use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\TaxiFleet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages\CreateTaxiFleet;
use App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages\EditTaxiFleet;
use App\Filament\Tenant\Resources\Taxi\TaxiFleetResource\Pages\ListTaxiFleets;

final class TaxiFleetResource extends Resource
{
    protected static ?string $model = TaxiFleet::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Taxi Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('tenant_id'),
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
            RelationManagers\DriversRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxiFleets::route('/'),
            'create' => CreateTaxiFleet::route('/create'),
            'edit' => EditTaxiFleet::route('/{record}/edit'),
        ];
    }
}
