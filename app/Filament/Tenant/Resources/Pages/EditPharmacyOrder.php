<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditPharmacyOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    ViewAction, DeleteAction};

    final class EditPharmacyOrder extends EditRecord
    {
        protected static string $resource = PharmacyOrderResource::class;

        public function getTitle(): string
        {
            return 'Edit PharmacyOrder';
        }

        protected function getHeaderActions(): array
        {
            return [
                ViewAction::make(),
                DeleteAction::make(),
            ];
        }
}
