<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerProduct;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FlowerProductResource extends Resource
{
    protected static ?string $model = FlowerProduct::class;
    protected static ?string $slug = 'flower-products';
    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Select::make('category')->options([
                'bouquet' => 'Букет',
                'arrangement' => 'Аранжировка',
                'subscription' => 'Подписка',
            ])->required(),
            Forms\Components\TextInput::make('price')->numeric()->required(),
            Forms\Components\TextInput::make('stock')->numeric(),
            Forms\Components\Toggle::make('in_stock'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('category'),
            Tables\Columns\TextColumn::make('price'),
            Tables\Columns\TextColumn::make('stock'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }
}
