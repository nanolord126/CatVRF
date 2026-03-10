<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Filter;
use Filament\{Forms, Tables, Resources\Resource};

class FilterResource extends Resource
{
    protected static ?string $model = Filter::class;
    protected static ?string $navigationGroup = 'Catalog';

    public static function form(Forms\Form $form): Forms\Form {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('vertical')->options(['Beauty' => 'Beauty', 'RealEstate' => 'Real Estate']),
            Forms\Components\Select::make('type')->options(['select' => 'Select', 'range' => 'Range', 'color' => 'Color']),
            Forms\Components\HasManyRepeater::make('values')
                ->relationship('values')
                ->schema([
                    Forms\Components\TextInput::make('value')->required(),
                    Forms\Components\TextInput::make('label')->required(),
                ])
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('vertical')->badge(),
            Tables\Columns\TextColumn::make('type')
        ]);
    }
    
    public static function getPages(): array {
        return [
            'index' => \App\Filament\Tenant\Resources\FilterResource\Pages\ListFilters::route('/'),
        ];
    }
}
