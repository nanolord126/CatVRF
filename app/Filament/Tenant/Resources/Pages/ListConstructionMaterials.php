<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListConstructionMaterials extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    EditAction, DeleteAction};
    use Filament\Tables\Actions\DeleteBulkAction;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Tables\Table;
    use Illuminate\Database\Eloquent\Builder;

    final class ListConstructionMaterials extends ListRecords
    {
        protected static string $resource = ConstructionMaterialsResource::class;

        public function getTitle(): string
        {
            return 'List ConstructionMaterials';
        }

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make(),
            ];
        }

        public function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('id')->sortable(),
                    TextColumn::make('created_at')->dateTime()->sortable(),
                ])
                ->filters([])
                ->actions([EditAction::make(), DeleteAction::make()])
                ->bulkActions([DeleteBulkAction::make()]);
        }
}
