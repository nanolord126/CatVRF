<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\CreateBouquet;
use App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\EditBouquet;
use App\Domains\Flowers\Filament\Resources\BouquetResource\Pages\ListBouquets;
use App\Domains\Flowers\Models\Bouquet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * BouquetResource — CatVRF 2026 Component.
 *
 * Filament resource for managing bouquets.
 * Tenant-scoped: all data filtered by current tenant.
 *
 * @package App\Domains\Flowers\Filament\Resources
 */
final class BouquetResource extends Resource
{
    protected static ?string $model = Bouquet::class;

    protected static ?string $navigationGroup = 'Цветы';

    protected static ?string $navigationLabel = 'Букеты';

    public static function form(Form $form): Form
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

    public static function table(Table $table): Table
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
            'index' => ListBouquets::route('/'),
            'create' => CreateBouquet::route('/create'),
            'edit' => EditBouquet::route('/{record}/edit'),
        ];
    }
}
