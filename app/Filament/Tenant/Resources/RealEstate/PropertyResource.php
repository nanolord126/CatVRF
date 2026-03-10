<?php

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Models\RealEstate\Property;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationGroup = 'Real Estate 2026';
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->options(['apartment' => 'Apartment', 'land' => 'Land', 'commercial' => 'Commercial', 'business' => 'Business', 'rental' => 'Rental'])
                ->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('area')->numeric()->suffix('sqm')->required(),
            Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
            Forms\Components\KeyValue::make('geo_data'),
            Forms\Components\KeyValue::make('amenities'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('type')->badge(),
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('area')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('price')->money()->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->color('gray'),
        ])->filters([]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages\ListProperties::route('/')];
    }
}
