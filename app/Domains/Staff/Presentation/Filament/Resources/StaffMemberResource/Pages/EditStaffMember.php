<?php

declare(strict_types=1);

/**
 * Class EditStaffMember
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
final class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Обновляем correlation_id перед каждым сохранением изменений.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = Uuid::uuid4()->toString();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
