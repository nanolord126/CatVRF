<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VIPBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EditVIPBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = VIPBookingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
                Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
            ];
        }

        protected function mutateFormDataBeforeSave(array $data): array
        {
            $data['correlation_id'] = (string) Str::uuid();

            Log::channel('audit')->info('Editing VIP Booking via Filament', [
                'booking_id' => $this->record->id,
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
