<?php

namespace App\Filament\Admin\Resources\Geo;

use App\Domains\Geo\Models\{Country, Region, District, City, Area};
use Filament\{Forms, Forms\Form, Resources\Resource, Tables, Tables\Table};
use App\Filament\Admin\Resources\Geo\Pages;

class GeoHierarchyResource extends Resource
{
    protected static ?string $model = Country::class;
    protected static ?string $navigationGroup = 'Geography';

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('code'),
            Forms\Components\Select::make('country_id')
                ->relationship('country', 'name')->reactive()
                ->hidden(fn ($get) => !$get('country_id')),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('code'),
            Tables\Columns\TextColumn::make('regions_count')->counts('regions'),
        ]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ListGeo::route('/')];
    }
}
