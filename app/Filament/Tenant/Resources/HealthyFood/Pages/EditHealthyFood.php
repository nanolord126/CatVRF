<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\HealthyFood\Pages;
use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordHealthyFood extends EditRecord {
    protected static string $resource = HealthyFoodResource::class;
}
