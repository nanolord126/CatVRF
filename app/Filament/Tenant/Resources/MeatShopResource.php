<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\MeatShops\Models\MeatShop;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class MeatShopResource extends Resource
{
    protected static ?string $model = MeatShop::class;
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Food';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            Select::make('meat_type')->options([
                'beef' => 'Говядина', 'pork' => 'Свинина', 'chicken' => 'Курица',
                'lamb' => 'Баранина', 'mixed' => 'Смешанное',
            ])->required(),
            Select::make('cut')->options([
                'steak' => 'Стейк', 'fillet' => 'Филе', 'ground' => 'Фарш',
                'ribs' => 'Рёбра', 'shoulder' => 'Плечо',
            ])->required(),
            TextInput::make('weight_g')->numeric(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('current_stock')->numeric(),
            Toggle::make('is_certified'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('meat_type'),
            TextColumn::make('cut'),
            TextColumn::make('weight_g'),
            TextColumn::make('price')->formatStateUsing(fn($s) => $s . ' ₽'),
            IconColumn::make('is_certified')->boolean(),
            TextColumn::make('rating'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkDeleteAction::make(),
        ]);
    }
}
