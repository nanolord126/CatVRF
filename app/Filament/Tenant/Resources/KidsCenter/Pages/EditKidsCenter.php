<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\KidsCenter\Pages;
use App\Filament\Tenant\Resources\KidsCenterResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordKidsCenter extends EditRecord {
    protected static string $resource = KidsCenterResource::class;
}
