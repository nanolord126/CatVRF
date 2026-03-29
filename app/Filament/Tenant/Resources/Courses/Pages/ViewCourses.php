<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Courses\Pages;
use App\Filament\Tenant\Resources\CoursesResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordCourses extends ViewRecord {
    protected static string $resource = CoursesResource::class;
}
