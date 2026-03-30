<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoRepairOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ListAutoRepairOrders extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoRepairOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Открыть заказ-наряд')
                    ->icon('heroicon-o-plus'),
            ];
        }

        /**
         * Tenant scoping.
         */
        protected function getTableQuery(): Builder
        {
            return parent::getTableQuery()
                ->where('tenant_id', tenant()->id);
        }
}
