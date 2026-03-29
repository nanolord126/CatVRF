<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FarmOrder\Pages;
use App\Filament\Tenant\Resources\FarmOrderResource;
use Filament\Resources\Pages\ViewRecord;
final class ViewRecordFarmOrder extends ViewRecord {
    protected static string $resource = FarmOrderResource::class;
}
