<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListAutoParts extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoPartResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Добавить запчасть')
                    ->icon('heroicon-o-plus'),
            ];
        }

        protected function getTableQuery(): Builder
        {
            return parent::getTableQuery()
                ->where('tenant_id', tenant()->id);
        }
}
