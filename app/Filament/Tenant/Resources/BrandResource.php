<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Brand;
use Filament\{Forms, Tables, Resources\Resource};

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;
    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->required(),
            Forms\Components\TextInput::make('country'),
            Forms\Components\FileUpload::make('logo')
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('country')->searchable(),
            Tables\Columns\ImageColumn::make('logo')
        ]);
    }
    
    public static function getPages(): array {
        return [
            'index' => \App\Filament\Tenant\Resources\BrandResource\Pages\ListBrands::route('/'),
        ];
    }
}
