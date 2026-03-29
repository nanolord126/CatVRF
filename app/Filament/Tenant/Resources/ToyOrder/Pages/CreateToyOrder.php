<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\ToyOrder\Pages;
use App\Filament\Tenant\Resources\ToyOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordToyOrder extends CreateRecord {
    protected static string $resource = ToyOrderResource::class;
}
