<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\DentalClinicResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateDentalClinic extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = DentalClinicResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

            return $data;
        }

        protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
        {
            return DB::transaction(function () use ($data) {
                $record = parent::handleRecordCreation($data);

                Log::channel('audit')->info('Dental Clinic Created', [
                    'clinic_id' => $record->id,
                    'name' => $record->name,
                    'correlation_id' => $data['correlation_id']
                ]);

                return $record;
            });
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
