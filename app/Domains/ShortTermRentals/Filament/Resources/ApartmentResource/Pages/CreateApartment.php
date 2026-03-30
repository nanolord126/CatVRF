<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateApartment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ApartmentResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = tenant()->id;
            $data['owner_id'] = auth()->id();
            $data['uuid'] = \Illuminate\Support\Str::uuid();
            $data['correlation_id'] = \Illuminate\Support\Str::uuid();
            return $data;
        }
}
