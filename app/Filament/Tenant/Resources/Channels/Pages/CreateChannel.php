<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CreateChannel extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static string $resource = ChannelResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['uuid']           = Str::uuid()->toString();
            $data['correlation_id'] = Str::uuid()->toString();
            $data['tenant_id']      = filament()->getTenant()?->id ?? '0';
            $data['slug']           = Str::slug($data['name'] ?? 'channel') . '-' . Str::random(6);

            return $data;
        }

        protected function getRedirectUrl(): string
        {
            return $this->getResource()::getUrl('index');
        }
}
