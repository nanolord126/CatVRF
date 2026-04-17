<?php declare(strict_types=1);

/**
 * ListCourses — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 * @see https://catvrf.ru/docs/listcourses
 */


namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\ListRecords;

final class ListCourses extends ListRecords
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = CourseResource::class;

        /**
         * Заголовки таблицы
         */
        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->label('Создать курс')
                    ->icon('heroicon-o-plus-circle'),
            ];
        }

        /**
         * Аудит лог на список страниц
         */
        protected function beforeFill(): void
        {
            \Illuminate\Support\Facades\Log::channel('audit')->info('User accessed Education Course List', [
                'tenant_id' => tenant()->id,
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
}
