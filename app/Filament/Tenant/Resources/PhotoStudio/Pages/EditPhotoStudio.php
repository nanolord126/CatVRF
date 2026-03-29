<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoStudio\Pages;
use App\Filament\Tenant\Resources\PhotoStudioResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordPhotoStudio extends EditRecord {
    protected static string $resource = PhotoStudioResource::class;
}
