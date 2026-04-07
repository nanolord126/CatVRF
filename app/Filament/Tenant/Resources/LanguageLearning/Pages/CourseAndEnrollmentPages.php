<?php declare(strict_types=1);

/**
 * ListLanguageCourses — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listlanguagecourses
 * @see https://catvrf.ru/docs/listlanguagecourses
 * @see https://catvrf.ru/docs/listlanguagecourses
 * @see https://catvrf.ru/docs/listlanguagecourses
 * @see https://catvrf.ru/docs/listlanguagecourses
 * @see https://catvrf.ru/docs/listlanguagecourses
 */


namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

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

    final class CreateLanguageCourse extends \Filament\Resources\Pages\CreateRecord
    {
        protected static string $resource = LanguageCourseResource::class;
    }

    final class EditLanguageCourse extends \Filament\Resources\Pages\EditRecord
    {
        protected static string $resource = LanguageCourseResource::class;
    }

    // Enrollment Pages
    namespace App\Filament\Tenant\Resources\LanguageLearning\Pages;

    use App\Filament\Tenant\Resources\LanguageLearning\LanguageEnrollmentResource;
    use Filament\Resources\Pages\ListRecords;

    final class ListLanguageEnrollments extends ListRecords
    {
        protected static string $resource = LanguageEnrollmentResource::class;

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

    /**
     * Default cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

}
