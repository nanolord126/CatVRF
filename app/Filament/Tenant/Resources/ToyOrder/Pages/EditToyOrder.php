<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ToyOrder\Pages;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordToyOrder extends EditRecord {
    protected static string $resource = ToyOrderResource::class;
}
