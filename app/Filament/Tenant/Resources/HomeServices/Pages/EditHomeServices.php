<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\HomeServices\Pages;
use App\Filament\Tenant\Resources\HomeServicesResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordHomeServices extends EditRecord {
    protected static string $resource = HomeServicesResource::class;
}
