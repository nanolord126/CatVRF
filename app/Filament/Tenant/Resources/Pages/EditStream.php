<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditStream extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    ViewAction, DeleteAction};

    final class EditStream extends EditRecord
    {
        protected static string $resource = StreamResource::class;

        public function getTitle(): string
        {
            return 'Edit Stream';
        }

        protected function getHeaderActions(): array
        {
            return [
                ViewAction::make(),
                DeleteAction::make(),
            ];
        }
}
