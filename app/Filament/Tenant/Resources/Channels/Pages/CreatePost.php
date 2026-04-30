<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Channels\Pages;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\CreateRecord;
use App\Domains\Content\Channels\Models\BusinessChannel;

final class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']        = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();
        $data['tenant_id']   = filament()->getTenant()?->id ?? '0';

        // channel_id — найти канал тенанта
        $channel = BusinessChannel::withoutGlobalScopes()
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
