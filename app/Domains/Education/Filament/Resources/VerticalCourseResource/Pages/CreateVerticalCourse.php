<?php declare(strict_types=1);

namespace App\Domains\Education\Filament\Resources\VerticalCourseResource\Pages;

use App\Domains\Education\Filament\Resources\VerticalCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVerticalCourse extends CreateRecord
{
    protected static string $resource = VerticalCourseResource::class;
}
