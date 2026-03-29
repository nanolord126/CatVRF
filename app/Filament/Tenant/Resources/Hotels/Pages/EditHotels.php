<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Hotels\Pages;
use App\Filament\Tenant\Resources\HotelsResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordHotels extends EditRecord {
    protected static string $resource = HotelsResource::class;
}
