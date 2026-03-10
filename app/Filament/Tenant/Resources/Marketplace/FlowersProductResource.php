<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\FlowersProductResource\Pages;
use App\Models\Tenants\FlowersProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FlowersProductResource extends Resource
{
    protected static ?string $model = FlowersProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = '🛒 Marketplace';
    protected static ?string $modelLabel = 'Товар (Цветы)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->maxLength(255)->label('Название'),
                        Forms\Components\Textarea::make('description')->label('Описание'),
                        Forms\Components\TextInput::make('price')->numeric()->prefix('₽')->required()->label('Цена'),
                        Forms\Components\TextInput::make('stock_quantity')->numeric()->default(0)->label('Запас'),
                        Forms\Components\Toggle::make('is_active')->default(true)->label('Активен'),
                    ]),
                Forms\Components\Section::make('Состав и Фото')
                    ->schema([
                        Forms\Components\Repeater::make('composition')
                            ->schema([
                                Forms\Components\TextInput::make('flower')->required()->label('Цветок'),
                                Forms\Components\TextInput::make('count')->numeric()->required()->label('Кол-во'),
                            ])->label('Состав букета'),
                        Forms\Components\FileUpload::make('photo_url')->image()->label('Фото'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')->label('Фото'),
                Tables\Columns\TextColumn::make('name')->searchable()->label('Название'),
                Tables\Columns\TextColumn::make('price')->money('RUB')->sortable()->label('Цена'),
                Tables\Columns\TextColumn::make('stock_quantity')->label('Запас'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Активен'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlowersProducts::route('/'),
            'create' => Pages\CreateFlowersProduct::route('/create'),
            'edit' => Pages\EditFlowersProduct::route('/{record}/edit'),
        ];
    }
}
