<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoRepairOrder\Pages;
use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordAutoRepairOrder extends CreateRecord {
    protected static string $resource = AutoRepairOrderResource::class;
}
