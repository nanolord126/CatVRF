<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages;

use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ListStaffMembers
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
final class ListStaffMembers extends ListRecords
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить сотрудника'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return StaffMemberResource::getEloquentQuery();
    }
}
