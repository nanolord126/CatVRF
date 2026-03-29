<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\WellnessCenter\Pages;
use App\Filament\Tenant\Resources\WellnessCenterResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordWellnessCenter extends EditRecord {
    protected static string $resource = WellnessCenterResource::class;
}
