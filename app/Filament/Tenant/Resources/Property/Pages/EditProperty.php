<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Property\Pages;
use App\Filament\Tenant\Resources\PropertyResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordProperty extends EditRecord {
    protected static string $resource = PropertyResource::class;
}
