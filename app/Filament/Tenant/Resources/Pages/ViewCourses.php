<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\Pages;

use use App\Filament\Tenant\Resources\CoursesResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewCourses extends ViewRecord
{
    protected static string $resource = CoursesResource::class;

    public function getTitle(): string
    {
        return 'View Courses';
    }
}