<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\BookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = BookingResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Entertainment Booking record mutation before creation', [
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $data['correlation_id'],
                'user_id' => auth()->id(),
            ]);

            return $data;
        }

        protected function afterCreate(): void
        {
            Log::channel('audit')->info('Entertainment Booking record created successfully', [
                'booking_id' => $this->record->id,
                'correlation_id' => $this->record->correlation_id,
                'user_id' => auth()->id(),
            ]);
        }
}
