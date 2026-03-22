<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\Bouquet;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

final class BouquetResource extends Resource
{
    protected static ?string $model = Bouquet::class;
    protected static ?string $navigationGroup = 'Цветы';
    protected static ?string $navigationLabel = 'Букеты';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\TextInput::make('price')->numeric()->required(),
            Forms\Components\Toggle::make('is_available')->default(true),
            Forms\Components\KeyValue::make('flowers_composition')->required(),
            Forms\Components\KeyValue::make('consumables_json'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('price')->money('RUB'),
            Tables\Columns\IconColumn::make('is_available')->boolean(),
        ])->filters([])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\ListBouquets::route('/'),
            'create' => \App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\CreateBouquet::route('/create'),
            'edit' => \App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\EditBouquet::route('/{record}/edit'),
        ];
    }
}
