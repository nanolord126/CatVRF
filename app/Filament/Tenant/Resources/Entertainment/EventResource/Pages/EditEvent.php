<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = EventResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function beforeSave(): void
        {
            Log::channel('audit')->info('Entertainment Event modification started', [
                'event_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->record->correlation_id,
            ]);
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('Entertainment Event modification completed', [
                'event_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
}
