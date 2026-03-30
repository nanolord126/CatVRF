<?php declare(strict_types=1);

namespace App\Domains\Auto\Filament\Resources\CarWashBookingResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ViewCarWashBooking extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = CarWashBookingResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\EditAction::make(),

                Actions\DeleteAction::make()
                    ->after(function () {
                        Log::channel('audit')->info('Car wash booking deleted from view page', [
                            'correlation_id' => $this->record->correlation_id,
                            'booking_id' => $this->record->id,
                            'user_id' => auth()->id(),
                        ]);
                    }),
            ];
        }

        protected function mutateFormDataBeforeFill(array $data): array
        {
            Log::channel('audit')->info('Car wash booking viewed', [
                'correlation_id' => $this->record->correlation_id,
                'booking_id' => $this->record->id,
                'wash_type' => $this->record->wash_type,
                'status' => $this->record->status,
                'user_id' => auth()->id(),
            ]);

            return $data;
        }
}
