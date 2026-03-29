<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\HealthyFood\Pages;
use App\Filament\Tenant\Resources\HealthyFoodResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordHealthyFood extends CreateRecord {
    protected static string $resource = HealthyFoodResource::class;
}
