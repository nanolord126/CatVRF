<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditTicket extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TicketResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ];
        }

        protected function beforeSave(): void
        {
            Log::channel('audit')->info('Entertainment Ticket modification', [
                'ticket_id' => $this->record->id,
                'correlation_id' => $this->record->correlation_id,
            ]);
        }
}
