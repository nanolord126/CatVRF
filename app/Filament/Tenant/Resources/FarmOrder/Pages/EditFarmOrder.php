<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FarmOrder\Pages;
use App\Filament\Tenant\Resources\FarmOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordFarmOrder extends EditRecord {
    protected static string $resource = FarmOrderResource::class;
}
