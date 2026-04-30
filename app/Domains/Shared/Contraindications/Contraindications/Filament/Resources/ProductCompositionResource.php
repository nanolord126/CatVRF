<?php

declare(strict_types=1);

namespace Modules\Contraindications\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Contraindications\Infrastructure\Models\ProductCompositionModel;

final class ProductCompositionResource extends Resource
{
    protected static ?string $model = ProductCompositionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Health & Safety';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('composable_type')
                    ->options([
                        'Modules\\BeautyMasters\\Domain\\Entities\\Service' => 'Beauty Service',
                        'Modules\\VetGrooming\\Domain\\Entities\\Service' => 'Vet Service',
                        'Modules\\Fitness\\Domain\\Entities\\Service' => 'Fitness Service',
                        'Modules\\Restaurant\\Domain\\Entities\\Product' => 'Food Product',
                    ])
                    ->required()
                    ->label('Service/Product Type'),
                Forms\Components\TextInput::make('composable_id')
                    ->required()
                    ->numeric()
                    ->label('Service/Product ID'),
                Forms\Components\TagsInput::make('ingredients')
                    ->label('Ingredients')
                    ->separator(','),
                Forms\Components\TextInput::make('calories_per_100g')
                    ->numeric()
                    ->step(0.01)
                    ->label('Calories per 100g'),
                Forms\Components\TextInput::make('proteins')
                    ->numeric()
                    ->step(0.01)
                    ->label('Proteins (g)'),
                Forms\Components\TextInput::make('fats')
                    ->numeric()
                    ->step(0.01)
                    ->label('Fats (g)'),
                Forms\Components\TextInput::make('carbs')
                    ->numeric()
                    ->step(0.01)
                    ->label('Carbs (g)'),
                Forms\Components\TagsInput::make('allergens')
                    ->label('Allergens')
                    ->separator(',')
                    ->helperText('Common allergens present in this product'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('composable_type')
                    ->searchable()
                    ->toggleable()
                    ->label('Type'),
                Tables\Columns\TextColumn::make('composable_id')
                    ->searchable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('ingredients')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('calories_per_100g')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('allergens')
                    ->badge()
                    ->separator(',')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
}
