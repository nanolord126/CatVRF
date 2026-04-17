<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use App\Filament\Tenant\Resources\LanguageLearning\LanguageCourseResource;
use App\Filament\Tenant\Resources\LanguageLearning\LanguageEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

final class ListLanguageCourses extends ListRecords
{
    protected static string $resource = LanguageCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

final class CreateLanguageCourse extends CreateRecord
{
    protected static string $resource = LanguageCourseResource::class;
}

final class EditLanguageCourse extends EditRecord
{
    protected static string $resource = LanguageCourseResource::class;
}

final class ListLanguageEnrollments extends ListRecords
{
    protected static string $resource = LanguageEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
