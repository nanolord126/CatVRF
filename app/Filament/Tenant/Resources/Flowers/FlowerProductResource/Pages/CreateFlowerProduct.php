<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\FlowerProductResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateFlowerProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = FlowerProductResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid'] = (string) Str::uuid();
            $data['tenant_id'] = tenant()->id ?? null;
            $data['correlation_id'] = (string) Str::uuid();

            return $data;
        }
}
