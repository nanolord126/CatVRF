<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Enrollment\Pages;

use use App\Filament\Tenant\Resources\EnrollmentResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    public function getTitle(): string
    {
        return 'Edit Enrollment';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}