<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoRepairOrder\Pages;
use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordAutoRepairOrder extends EditRecord {
    protected static string $resource = AutoRepairOrderResource::class;
}
