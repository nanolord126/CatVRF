<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\WellnessCenter\Pages;
use App\Filament\Tenant\Resources\WellnessCenterResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordWellnessCenter extends CreateRecord {
    protected static string $resource = WellnessCenterResource::class;
}
