<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MedicalClinicResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateRecordMedicalClinic extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = MedicalClinicResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string)Str::uuid();
            $data['tenant_id'] = tenant()->id;
            $data['correlation_id'] = (string)Str::uuid();

            return $data;
        }
}
