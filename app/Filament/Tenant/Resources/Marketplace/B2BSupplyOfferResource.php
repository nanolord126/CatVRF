<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\B2BSupplyOfferResource\Pages;
use App\Models\Tenants\B2BSupplyOffer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class B2BSupplyOfferResource extends Resource
{
    protected static ?string $model = B2BSupplyOffer::class;
    protected static ?string $navigationGroup = 'Ecosystem';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $label = 'B2B Supply';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('manufacturer_name')->required(),
                Forms\Components\TextInput::make('product_name')->required(),
                Forms\Components\TextInput::make('wholesale_price')->numeric()->prefix('$')->required(),
                Forms\Components\TextInput::make('min_batch')->numeric()->label('Minimum Batch')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('manufacturer_name')->searchable(),
                Tables\Columns\TextColumn::make('product_name')->searchable(),
                Tables\Columns\TextColumn::make('wholesale_price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('min_batch')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BSupplyOffers::route('/'),
            'create' => Pages\CreateB2BSupplyOffer::route('/create'),
            'edit' => Pages\EditB2BSupplyOffer::route('/{record}/edit'),
        ];
    }
}
