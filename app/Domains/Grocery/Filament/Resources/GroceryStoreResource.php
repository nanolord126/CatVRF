declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Grocery\Filament\Resources;

use App\Domains\Grocery\Models\GroceryStore;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

final /**
 * GroceryStoreResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class GroceryStoreResource extends Resource
{
    protected static ?string $model = GroceryStore::class;
    protected static ?string $navigationGroup = 'Супермаркеты';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\Select::make('store_type')
                ->options([
                    'supermarket' => 'Супермаркет',
                    'cafe' => 'Кафе',
                    'butcher' => 'Мясная лавка',
                    'greengrocer' => 'Овощная лавка',
                ])->required(),
            Forms\Components\TagsInput::make('cuisines'),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('store_type'),
            Tables\Columns\TextColumn::make('rating')->numeric(1),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages\ListGroceryStores::route('/'),
            'create' => \App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages\CreateGroceryStore::route('/create'),
            'edit' => \App\Domains\Grocery\Filament\Resources\GroceryStoreResource\Pages\EditGroceryStore::route('/{record}/edit'),
        ];
    }
}
