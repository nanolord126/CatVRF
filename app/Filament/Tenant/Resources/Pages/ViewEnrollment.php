<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Enrollment\Pages;

use use App\Filament\Tenant\Resources\EnrollmentResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    public function getTitle(): string
    {
        return 'View Enrollment';
    }
}