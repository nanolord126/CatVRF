<?php

namespace App\Filament\Tenant\Resources\CourseResource\Pages;

use App\Filament\Tenant\Resources\CourseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;
}
