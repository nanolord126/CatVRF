<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\LuxuryProduct\Pages;
use App\Filament\Tenant\Resources\LuxuryProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordLuxuryProduct extends EditRecord {
    protected static string $resource = LuxuryProductResource::class;
}
