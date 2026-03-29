<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Courses\Pages;
use App\Filament\Tenant\Resources\CoursesResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsCourses extends ListRecords {
    protected static string $resource = CoursesResource::class;
}
