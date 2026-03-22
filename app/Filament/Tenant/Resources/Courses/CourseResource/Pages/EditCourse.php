<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\CourseResource\Pages;

use App\Filament\Tenant\Resources\Courses\CourseResource;
use Filament\Resources\Pages\EditRecord;

final class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
