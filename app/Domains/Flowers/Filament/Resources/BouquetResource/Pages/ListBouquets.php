<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources\BouquetResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListBouquets extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BouquetResource::class;

        protected function getHeaderActions(): array
        {
            return [
                \Filament\Actions\CreateAction::make(),
            ];
        }

        protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
        {
            return parent::getTableQuery()->where('tenant_id', tenant()->id);
        }
}
