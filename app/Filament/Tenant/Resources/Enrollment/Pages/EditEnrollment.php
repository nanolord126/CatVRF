<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Enrollment\Pages;
use App\Filament\Tenant\Resources\EnrollmentResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordEnrollment extends EditRecord {
    protected static string $resource = EnrollmentResource::class;
}
