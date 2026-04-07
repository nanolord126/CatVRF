<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;

use App\Filament\Tenant\Resources\Beauty\MasterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateMaster — Filament Page (Layer 9).
 *
 * Tenant-scoped master creation with correlation_id tracing.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\MasterResource\Pages
 */
final class CreateMaster extends CreateRecord
{
    protected static string $resource = MasterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = filament()->getTenant()?->id;
        $data['uuid']           ??= Str::uuid()->toString();
        $data['correlation_id'] ??= Str::uuid()->toString();

        app(LoggerInterface::class)->info('Beauty: создание мастера', [
            'tenant_id'      => $data['tenant_id'],
            'name'           => $data['name'] ?? null,
            'correlation_id' => $data['correlation_id'],
        ]);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Get the string representation of this object.
     *
     * @return string
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new');
    }
}
