<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateTicket extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = TicketResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Entertainment Ticket record mutation', [
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }
}
