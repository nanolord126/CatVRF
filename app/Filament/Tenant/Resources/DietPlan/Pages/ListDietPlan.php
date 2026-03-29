<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DietPlan\Pages;
use App\Filament\Tenant\Resources\DietPlanResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsDietPlan extends ListRecords {
    protected static string $resource = DietPlanResource::class;
}
