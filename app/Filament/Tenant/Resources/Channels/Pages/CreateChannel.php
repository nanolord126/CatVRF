<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Channels\Pages;

use App\Filament\Tenant\Resources\Channels\ChannelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final /**
 * CreateChannel
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreateChannel extends CreateRecord
{
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
