<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages;

use App\Filament\Tenant\Resources\Beauty\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateAppointment — Filament Page (Layer 9).
 *
 * Tenant-scoped appointment creation with correlation_id tracing.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\AppointmentResource\Pages
 */
final class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id']      = filament()->getTenant()?->id;
        $data['uuid']           ??= Str::uuid()->toString();
        $data['correlation_id'] ??= Str::uuid()->toString();
        $data['status']         ??= 'pending';

        app(LoggerInterface::class)->info('Beauty: создание записи', [
            'tenant_id'      => $data['tenant_id'],
            'client_id'      => $data['client_id'] ?? null,
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
