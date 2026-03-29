<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Master\Pages;
use App\Filament\Tenant\Resources\MasterResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordMaster extends ViewRecord {
    protected static string $resource = MasterResource::class;
}
