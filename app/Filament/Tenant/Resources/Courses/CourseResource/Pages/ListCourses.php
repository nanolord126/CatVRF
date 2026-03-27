<?php

declare(strict_types=1);


namespace App\Filament\Tenant\Resources\Courses\CourseResource\Pages;

use App\Filament\Tenant\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ListRecords;

final /**
 * ListCourses
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
