<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateVIPBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VIPBookingResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Creating VIP Booking via Filament', [
                'client_id' => $data['client_id'] ?? 'N/A',
                'user_id' => auth()->id(),
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
