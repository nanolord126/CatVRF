<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVenue extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VenueResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function beforeSave(): void
        {
            Log::channel('audit')->info('Venue modification started', [
                'venue_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->record->correlation_id,
            ]);
        }

        protected function afterSave(): void
        {
            Log::channel('audit')->info('Venue modification completed', [
                'venue_id' => $this->record->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
}
