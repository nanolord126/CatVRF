<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\BeautySalonResource\Pages;
use App\Filament\Tenant\Resources\BeautySalonResource\RelationManagers;
use App\Models\BeautySalon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BeautySalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

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
            'index' => Pages\ListBeautySalons::route('/'),
            'create' => Pages\CreateBeautySalon::route('/create'),
            'edit' => Pages\EditBeautySalon::route('/{record}/edit'),
        ];
    }
}
