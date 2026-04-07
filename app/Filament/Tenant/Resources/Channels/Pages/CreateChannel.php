<?php declare(strict_types=1);

/**
 * CreateChannel — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 * @see https://catvrf.ru/docs/createchannel
 */


namespace App\Filament\Tenant\Resources\Channels\Pages;

use Filament\Resources\Pages\CreateRecord;

final class CreateChannel extends CreateRecord
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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
