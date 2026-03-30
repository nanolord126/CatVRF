<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditKidsCenter extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    ViewAction, DeleteAction};

    final class EditKidsCenter extends EditRecord
    {
        protected static string $resource = KidsCenterResource::class;

        public function getTitle(): string
        {
            return 'Edit KidsCenter';
        }

        protected function getHeaderActions(): array
        {
            return [
                ViewAction::make(),
                DeleteAction::make(),
            ];
        }
}
