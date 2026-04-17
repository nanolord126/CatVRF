<?php

declare(strict_types=1);

namespace App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource\Pages;

use App\Domains\Staff\Presentation\Filament\Resources\StaffMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Ramsey\Uuid\Uuid;

final class EditStaffMember extends EditRecord
{
    protected static string $resource = StaffMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

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