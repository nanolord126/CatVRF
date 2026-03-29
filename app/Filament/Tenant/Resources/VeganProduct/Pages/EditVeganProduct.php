<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\VeganProduct\Pages;
use App\Filament\Tenant\Resources\VeganProductResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordVeganProduct extends EditRecord {
    protected static string $resource = VeganProductResource::class;
}
