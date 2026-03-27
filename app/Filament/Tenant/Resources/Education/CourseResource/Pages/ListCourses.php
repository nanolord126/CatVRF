<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;

use App\Filament\Tenant\Resources\Education\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: ListCourses (Education).
 */
final class ListCourses extends ListRecords
{
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
        Log::channel('audit')->info('User accessed Education Course List', [
            'tenant_id' => tenant()->id,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
