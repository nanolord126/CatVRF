<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\CourseResource\Pages;

use App\Filament\Tenant\Resources\Courses\CourseResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;
}
