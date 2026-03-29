<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Courses\Pages;
use App\Filament\Tenant\Resources\CoursesResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordCourses extends EditRecord {
    protected static string $resource = CoursesResource::class;
}
