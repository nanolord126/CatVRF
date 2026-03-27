<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Channels\Pages;

use App\Filament\Tenant\Resources\Channels\PostResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

final /**
 * CreatePost
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']        = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();
        $data['tenant_id']   = filament()->getTenant()?->id ?? '0';

        // channel_id — найти канал тенанта
        $channel = \App\Domains\Content\Channels\Models\BusinessChannel::withoutGlobalScopes()
            ->where('tenant_id', $data['tenant_id'])
            ->first();

        if ($channel === null) {
            throw new \RuntimeException('Сначала создайте канал бизнеса.');
        }

        $data['channel_id'] = $channel->id;
        $data['reactions']  = [];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
