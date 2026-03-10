<?php

namespace App\Filament\Tenant\Resources\Marketplace\EducationCourseResource\Pages;

use App\Filament\Tenant\Resources\Marketplace\EducationCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationCourses extends ListRecords
{
    protected static string $resource = EducationCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
