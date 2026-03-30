<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToyOrderResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditToyOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = \App\Filament\Tenant\Resources\ToyOrderResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('Toy Order Updated (Filament UI)', [
                'id' => $this->record->id,
                'status' => $this->record->status
            ]);
        }
}
