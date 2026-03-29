<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\Pages;

use use App\Filament\Tenant\Resources\CoursesResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourses extends CreateRecord
{
    protected static string $resource = CoursesResource::class;

    public function getTitle(): string
    {
        return 'Create Courses';
    }
}