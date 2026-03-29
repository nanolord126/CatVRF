<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoStudio\Pages;
use App\Filament\Tenant\Resources\PhotoStudioResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsPhotoStudio extends ListRecords {
    protected static string $resource = PhotoStudioResource::class;
}
