<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto\AutoRepairOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditAutoRepairOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = AutoRepairOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['total_cost_kopecks'] = ($data['labor_cost_kopecks'] ?? 0) + ($data['parts_cost_kopecks'] ?? 0);
            return $data;
        }
}
