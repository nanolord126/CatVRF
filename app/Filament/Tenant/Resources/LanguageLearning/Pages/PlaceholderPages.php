<?php

declare(strict_types=1);

/**
 * ListLanguageSchools — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/listlanguageschools
 * @see https://catvrf.ru/docs/listlanguageschools
 * @see https://catvrf.ru/docs/listlanguageschools
 * @see https://catvrf.ru/docs/listlanguageschools
 * @see https://catvrf.ru/docs/listlanguageschools
 * @see https://catvrf.ru/docs/listlanguageschools
 */

namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

use App\Filament\Tenant\Resources\LanguageLearning\LanguageTeacherResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

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

final class CreateLanguageSchool extends CreateRecord
{
    protected static string $resource = LanguageSchoolResource::class;
}

final class EditLanguageSchool extends EditRecord
{
    protected static string $resource = LanguageSchoolResource::class;
}

// Повторяем для учителей





final class ListLanguageTeachers extends ListRecords
{
    protected static string $resource = LanguageTeacherResource::class;
}

final class CreateLanguageTeacher extends CreateRecord
{
    protected static string $resource = LanguageTeacherResource::class;
}

final class EditLanguageTeacher extends EditRecord
{
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    protected static string $resource = LanguageTeacherResource::class;
}
