<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Enrollment\Pages;
use App\Filament\Tenant\Resources\EnrollmentResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordEnrollment extends ViewRecord {
    protected static string $resource = EnrollmentResource::class;
}
