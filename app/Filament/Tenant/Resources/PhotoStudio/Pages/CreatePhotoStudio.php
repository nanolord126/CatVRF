<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoStudio\Pages;
use App\Filament\Tenant\Resources\PhotoStudioResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordPhotoStudio extends CreateRecord {
    protected static string $resource = PhotoStudioResource::class;
}
