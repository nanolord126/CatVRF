<?php declare(strict_types=1);

namespace App\Domains\Food\Grocery\Filament\Resources\GroceryStoreResource\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateGroceryStore extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = GroceryStoreResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = tenant()->id;
            $data['uuid'] = \Illuminate\Support\Str::uuid();
            $data['correlation_id'] = \Illuminate\Support\Str::uuid();
            return $data;
        }
}
