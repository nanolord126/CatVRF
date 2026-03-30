<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StrApartmentResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateStrApartment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = StrApartmentResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data["uuid"] = (string) Str::uuid();
            $data["correlation_id"] = (string) Str::uuid();
            $data["tenant_id"] = tenant()->id;

            return $data;
        }
}
