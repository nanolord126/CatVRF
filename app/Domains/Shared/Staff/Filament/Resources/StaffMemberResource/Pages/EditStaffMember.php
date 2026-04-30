<?php

declare(strict_types=1);

namespace App\Domains\Staff\Filament\Resources\StaffMemberResource\Pages;

use App\Domains\Staff\Filament\Resources\StaffMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
