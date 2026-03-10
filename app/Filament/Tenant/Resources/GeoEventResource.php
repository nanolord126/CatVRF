<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\GeoEventResource\Pages;
use App\Filament\Tenant\Resources\GeoEventResource\RelationManagers;
use App\Models\GeoEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GeoEventResource extends Resource
{
    protected static ?string $model = GeoEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListGeoEvents::route('/'),
            'create' => Pages\CreateGeoEvent::route('/create'),
            'edit' => Pages\EditGeoEvent::route('/{record}/edit'),
        ];
    }
}
