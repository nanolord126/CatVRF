<?php declare(strict_types=1);

namespace App\Domains\Education\Filament\Resources\VerticalCourseResource\Pages;

use App\Domains\Education\Filament\Resources\VerticalCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVerticalCourse extends EditRecord
{
    protected static string $resource = VerticalCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
