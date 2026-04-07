<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages;

use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource;
use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Uuid;

/**
 * Class CreateStaffMember
 *
 * Part of the Staff vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages
 */
final class CreateStaffMember extends CreateRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Дополняем данные формы перед сохранением:
     * проставляем tenant_id и correlation_id.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();

        $data['tenant_id']      = $tenant?->getKey() ?? '';
        $data['uuid']           = Uuid::uuid4()->toString();
        $data['correlation_id'] = Uuid::uuid4()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
