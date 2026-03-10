<?php

namespace App\Filament\Tenant\Resources\Marketplace\EducationCourseResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\EducationCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationCourse extends EditRecord
{
    protected static string $resource = EducationCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
