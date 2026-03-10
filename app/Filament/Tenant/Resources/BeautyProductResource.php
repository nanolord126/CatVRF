<?php

namespace App\Filament\Tenant\Resources;

use App\Models\BeautyProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BeautyProductResource extends Resource
{
    protected static ?string $model = BeautyProduct::class;
    protected static ?string $navigationGroup = 'Beauty Shop';

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Select::make('type')->options(['inventory' => 'Inventory', 'cosmetics' => 'Cosmetics', 'perfumery' => 'Perfumery'])->required(),
            Forms\Components\TextInput::make('price')->numeric()->prefix('RUB')->required(),
            Forms\Components\TextInput::make('stock')->numeric()->required(),
            Forms\Components\FileUpload::make('images')->multiple()->directory('beauty-products')->image(),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\TextColumn::make('price')->money('RUB'),
            Tables\Columns\TextColumn::make('stock'),
        ]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ListBeautyProducts::route('/'), 'create' => Pages\CreateBeautyProduct::route('/create'), 'edit' => Pages\EditBeautyProduct::route('/{record}/edit')];
    }
}
