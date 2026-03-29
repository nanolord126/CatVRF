<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses\Pages;

use use App\Filament\Tenant\Resources\CoursesResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCourses extends EditRecord
{
    protected static string $resource = CoursesResource::class;

    public function getTitle(): string
    {
        return 'Edit Courses';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}