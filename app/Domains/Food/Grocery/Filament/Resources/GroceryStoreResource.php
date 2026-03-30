<?php declare(strict_types=1);

namespace App\Domains\Food\Grocery\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryStoreResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                'index' => \App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource\Pages\ListGroceryStores::route('/'),
                'create' => \App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource\Pages\CreateGroceryStore::route('/create'),
                'edit' => \App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource\Pages\EditGroceryStore::route('/{record}/edit'),
            ];
        }
}
