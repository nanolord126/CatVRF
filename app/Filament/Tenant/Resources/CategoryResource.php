<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Category;
use Filament\{Forms, Tables, Resources\Resource};

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->required(),
            Forms\Components\Select::make('parent_id')
                ->relationship('parent', 'name'),
            Forms\Components\Select::make('vertical')
                ->options([
                    'Beauty' => 'Beauty', 'RealEstate' => 'Real Estate',
                    'Hotels' => 'Hotels', 'Restaurants' => 'Restaurants'
                ])->required(),
            Forms\Components\Toggle::make('is_active')->default(true)
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('vertical')->badge(),
            Tables\Columns\TextColumn::make('parent.name')->label('Parent'),
            Tables\Columns\IconColumn::make('is_active')->boolean()
        ])->filters([]);
    }
    
    public static function getPages(): array {
        return [
            'index' => \App\Filament\Tenant\Resources\CategoryResource\Pages\ListCategories::route('/'),
            'create' => \App\Filament\Tenant\Resources\CategoryResource\Pages\CreateCategory::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\CategoryResource\Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
