<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\PhotoSession\Pages;
use App\Filament\Tenant\Resources\PhotoSessionResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordPhotoSession extends ViewRecord {
    protected static string $resource = PhotoSessionResource::class;
}
