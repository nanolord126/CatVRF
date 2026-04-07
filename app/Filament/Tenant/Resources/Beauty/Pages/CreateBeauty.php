<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateBeauty — Filament Page (Layer 9).
 *
 * Tenant-scoped salon creation with transaction + audit logging.
 * No constructor injection — services resolved via app().
 *
 * @package App\Filament\Tenant\Resources\Beauty\Pages
 */
final class CreateBeauty extends CreateRecord
{
    protected static string $resource = BeautyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $correlationId = Str::uuid()->toString();

        app(DatabaseManager::class)->transaction(function () use (&$data, $correlationId): void {
            $data['correlation_id'] = $correlationId;
            $data['tenant_id']      = filament()->getTenant()?->id;
            $data['uuid']           = Str::uuid()->toString();
            $data['is_verified']    = false;

            app(LoggerInterface::class)->info('Beauty salon creation form submitted', [
                'correlation_id' => $correlationId,
                'tenant_id'      => $data['tenant_id'],
                'user_id'        => filament()->auth()->id(),
                'salon_name'     => $data['name'] ?? null,
            ]);
        });

        return $data;
    }

    protected function afterCreate(): void
    {
        app(LoggerInterface::class)->info('Beauty salon created successfully', [
            'record_id'      => $this->record->id,
            'uuid'           => $this->record->uuid,
            'correlation_id' => $this->record->correlation_id,
            'user_id'        => filament()->auth()->id(),
            'tenant_id'      => filament()->getTenant()?->id,
            'timestamp'      => now()->toIso8601String(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
