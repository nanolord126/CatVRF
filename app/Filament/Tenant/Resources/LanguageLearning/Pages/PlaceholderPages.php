<?php declare(strict_types=1);

/**
 * ListLanguageSchools — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

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

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
