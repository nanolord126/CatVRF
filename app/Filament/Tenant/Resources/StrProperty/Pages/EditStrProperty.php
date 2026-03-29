<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\StrProperty\Pages;
use App\Filament\Tenant\Resources\StrPropertyResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordStrProperty extends EditRecord {
    protected static string $resource = StrPropertyResource::class;
}
