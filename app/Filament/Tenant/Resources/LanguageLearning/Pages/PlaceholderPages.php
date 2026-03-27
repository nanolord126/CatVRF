<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use App\Filament\Tenant\Resources\LanguageLearning\LanguageSchoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListLanguageSchools extends ListRecords
{
    protected static string $resource = LanguageSchoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

final class CreateLanguageSchool extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = LanguageSchoolResource::class;
}

final class EditLanguageSchool extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = LanguageSchoolResource::class;
}

// Повторяем для учителей
namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use App\Filament\Tenant\Resources\LanguageLearning\LanguageTeacherResource;
use Filament\Resources\Pages\ListRecords;

final class ListLanguageTeachers extends ListRecords
{
    protected static string $resource = LanguageTeacherResource::class;
}

final class CreateLanguageTeacher extends \Filament\Resources\Pages\CreateRecord
{
    protected static string $resource = LanguageTeacherResource::class;
}

final class EditLanguageTeacher extends \Filament\Resources\Pages\EditRecord
{
    protected static string $resource = LanguageTeacherResource::class;
}
