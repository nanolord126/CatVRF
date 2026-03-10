<?php

namespace App\Filament\Tenant\Resources;

use App\Models\MarketplaceGym as Gym;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GymResource extends Resource
{
    protected static ?string $model = Gym::class;
    protected static ?string $navigationGroup = 'Sports Module';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->required(),
                Forms\Components\KeyValue::make('geo_location')
                    ->label('Geo Coordinates')
                    ->helperText('Used for GeoLogistics and Demand Heatmaps'),
                Forms\Components\KeyValue::make('occupancy_data')
                    ->label('Occupancy / Attendance Data')
                    ->helperText('Dynamic tracking of gym load for heatmaps'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('address')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
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
            'index' => GymResource\Pages\ListGyms::route('/'),
            'create' => GymResource\Pages\CreateGym::route('/create'),
            'edit' => GymResource\Pages\EditGym::route('/{record}/edit'),
        ];
    }
}
