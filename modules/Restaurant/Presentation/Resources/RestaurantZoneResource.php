<?php

declare(strict_types=1);

namespace Modules\Restaurant\Presentation\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Restaurant\Infrastructure\Models\RestaurantZoneModel;

final class RestaurantZoneResource extends Resource
{
    protected static ?string $model = RestaurantZoneModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Зоны ресторана';

    protected static ?string $modelLabel = 'Зона';

    protected static ?string $navigationGroup = 'Ресторан';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о зоне')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Описание'),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Цвет'),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->label('Порядок отображения'),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активна'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Название'),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Цвет'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна'),

                Tables\Columns\TextColumn::make('table_count')
                    ->sortable()
                    ->label('Столов'),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable()
                    ->label('Порядок'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создано'),
            ])
            ->defaultSort('display_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
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

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Restaurant\Presentation\Resources\RestaurantZoneResource\Pages\ListRestaurantZones::route('/'),
            'create' => \Modules\Restaurant\Presentation\Resources\RestaurantZoneResource\Pages\CreateRestaurantZone::route('/create'),
            'edit' => \Modules\Restaurant\Presentation\Resources\RestaurantZoneResource\Pages\EditRestaurantZone::route('/{record}/edit'),
        ];
    }
}
