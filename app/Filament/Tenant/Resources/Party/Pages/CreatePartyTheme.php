<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreatePartyTheme extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = PartyThemeResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = tenant()->id ?? null;
            $data['correlation_id'] = (string) \Illuminate\Support\Str::uuid();

            return $data;
        }
}
