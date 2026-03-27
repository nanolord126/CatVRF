<?php

declare(strict_types=1);


namespace App\Domains\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerShop;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final /**
 * FlowerShopResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FlowerShopResource extends Resource
{
    protected static ?string $model = FlowerShop::class;
    protected static ?string $slug = 'flower-shops';
    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('city')->required(),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\TextInput::make('phone'),
            Forms\Components\Toggle::make('is_active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('city'),
            Tables\Columns\TextColumn::make('rating'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }
}
